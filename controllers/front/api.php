<?php

use Thirtybees\Core\DependencyInjection\ServiceLocatorCore;
use Thirtybees\Core\Error\ErrorUtils;
use Thirtybees\Module\POS\Api\Response\AccessDeniedResponse;
use Thirtybees\Module\POS\Api\Response\BadRequestResponse;
use Thirtybees\Module\POS\Api\Response\ForbiddenResponse;
use Thirtybees\Module\POS\Api\Response\GetSkuListResponse;
use Thirtybees\Module\POS\Api\Response\GetWorkstationListResponse;
use Thirtybees\Module\POS\Api\Response\InvalidAmountCollectedResponse;
use Thirtybees\Module\POS\Api\Response\InvalidOrderStatusResponse;
use Thirtybees\Module\POS\Api\Response\JSendErrorResponse;
use Thirtybees\Module\POS\Api\Response\JSendResponse;
use Thirtybees\Module\POS\Api\Response\MinimalQuantityRequiredResponse;
use Thirtybees\Module\POS\Api\Response\NotFoundResponse;
use Thirtybees\Module\POS\Api\Response\OrderListResponse;
use Thirtybees\Module\POS\Api\Response\OrderProcessResponse;
use Thirtybees\Module\POS\Api\Response\OrderResponse;
use Thirtybees\Module\POS\Api\Response\OutOfStockResponse;
use Thirtybees\Module\POS\Api\Response\PrintReceiptResponse;
use Thirtybees\Module\POS\Api\Response\SkuResponse;
use Thirtybees\Module\POS\Api\Response\UserResponse;
use Thirtybees\Module\POS\Auth\Model\Role;
use Thirtybees\Module\POS\Auth\Model\Token;
use Thirtybees\Module\POS\DependencyInjection\Factory;
use Thirtybees\Module\POS\Exception\AccessDeniedException;
use Thirtybees\Module\POS\Exception\ForbiddenException;
use Thirtybees\Module\POS\Exception\InvalidRequestException;
use Thirtybees\Module\POS\Exception\NotFoundException;
use Thirtybees\Module\POS\Exception\ServerErrorException;
use Thirtybees\Module\POS\OrderProcess\Model\OrderProcess;
use Thirtybees\Module\POS\Payment\Method\CashPaymentMethod;
use Thirtybees\Module\POS\Payment\Method\CreditCardOfflinePaymentMethod;
use Thirtybees\Module\POS\Payment\Method\PaymentMethod;
use Thirtybees\Module\POS\Sku\Service\SkuService;
use Thirtybees\Module\POS\Workstation\Model\Workstation;

/**
 * Copyright (C) 2022-2022 thirty bees <contact@thirtybees.com>
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the Thirty Bees Regular License version 1.0
 * For more information see LICENSE.txt file
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2022-2022 Petr Hucik
 * @license   Licensed under the Thirty Bees Regular License version 1.0
 */

class TbPOSApiModuleFrontController extends ModuleFrontController
{
    const METHOD_POST = 'POST';
    const METHOD_GET = 'GET';
    const METHOD_OPTIONS = 'OPTIONS';

    const DISCOUNT_PERCENTAGE = 'percentage';
    const DISCOUNT_AMOUNT = 'amount';

    /**
     * @var TbPOS
     */
    public $module;

    /**
     * @return void
     *
     * @throws PrestaShopException
     */
    public function init()
    {
        // Allow CORS requests
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");
        if ($method === static::METHOD_OPTIONS) {
            exit;
        }

        $this->initContext();

        $factory = $this->module->getFactory();
        try {
            $response = $this->processRequest($factory);
        } catch (Throwable $e) {
            $desc = ErrorUtils::describeException($e);
            ServiceLocatorCore::getInstance()->getErrorHandler()->logFatalError($desc);
            if (_PS_MODE_DEV_) {
            $message = $desc->getExtendedMessage();
            } else {
                $message = "Internal server error";
            }
            $response = new JSendErrorResponse($message);
        }
        $this->sendResponse($response->getResponse($factory), $response->getResponseCode() );
    }

    /**
     * @param Factory $factory
     *
     * @return JSendResponse
     *
     * @throws PrestaShopException
     */
    protected function processRequest(Factory $factory): JSendResponse
    {
        $factory = $this->module->getFactory();
        try {
            return $this->dispatch($factory, Tools::getValue('apiUrl'));
        } catch (AccessDeniedException $e) {
            return new AccessDeniedResponse($e->getMessage());
        } catch (ForbiddenException $e) {
            return new ForbiddenResponse($e->getMessage());
        } catch (InvalidRequestException $e) {
            return new BadRequestResponse($e->getMessage());
        }
    }

    /**
     * @param array $payload
     */
    protected function sendResponse($payload, int $responseCode = 200)
    {
        if (! headers_sent()) {
            header('Content-Type: application/json');
            $this->setResponseCode($responseCode);
        }
        die(json_encode($payload, JSON_PRETTY_PRINT));
    }

    /**
     * @param Factory $factory
     * @param string $url
     *
     * @return JSendResponse
     *
     * @throws AccessDeniedException
     * @throws ForbiddenException
     * @throws InvalidRequestException
     * @throws PrestaShopException
     */
    protected function dispatch(Factory $factory, string $url): JSendResponse
    {
        $url = trim($url, '/');
        if ($url === 'products') {
            $this->ensureMethod(static::METHOD_GET);
            $this->ensureAccess(Role::getRoles());
            return $this->processGetProducts($factory);
        }
        if ($url === 'products/find') {
            $this->ensureMethod(static::METHOD_POST);
            $this->ensureAccess(Role::getRoles());
            $body = $this->getBody();
            return $this->processGetProducts(
                $factory,
                $this->toSearchType($this->getParameter('type', $body), 'type'),
                $this->getParameter('term', $body)
            );
        }
        if (preg_match('#^products/([0-9]+)/([0-9]+)$#', $url, $matches)) {
            $this->ensureMethod(static::METHOD_GET);
            $this->ensureAccess(Role::getRoles());
            return $this->processGetProductById($factory, (int)$matches[1], (int)$matches[2]);
        }

        if (preg_match('#^products/(.*)$#', $url, $matches)) {
            $this->ensureMethod(static::METHOD_GET);
            $this->ensureAccess(Role::getRoles());
            return $this->processGetProductByReference($factory, $matches[1]);
        }

        if ($url === 'workstations') {
            $this->ensureMethod(static::METHOD_GET);
            return $this->processGetWorkstations($factory);
        }

        if ($url === 'token') {
            $this->ensureMethod(static::METHOD_GET);
            $token = $this->ensureAccess(Role::getRoles());
            return $this->processTokenIntrospection($factory, $token);
        }

        if ($url === 'users') {
            $this->ensureMethod(static::METHOD_POST);
            $body = $this->getBody();
            return $this->processLogin(
                $factory,
                (int)$this->getParameter('workstationId', $body),
                (string)$this->getParameter('username', $body),
                (string)$this->getParameter('password', $body),
                (string)$this->getParameter('role', $body),
            );
        }

        if ($url === 'token/exchange') {
            $this->ensureMethod(static::METHOD_POST);
            $token = $this->ensureAccess(Role::getRoles());
            return $this->processExchangeToken($factory, $token);
        }

        if ($url === 'orders') {
            $this->ensureMethod(static::METHOD_GET);
            $this->ensureAccess([ Role::ROLE_ADMIN, Role::ROLE_CASHIER ]);
            return $this->processListOrders(
                $factory,
                $this->getParameter('searchterm', $_GET, false, ''),
                (int)$this->getParameter('page', $_GET, false, 1),
                (int)$this->getParameter('per_page', $_GET, false, 8)
            );
        }

        if (preg_match('#^orders/([0-9]+)$#', $url, $matches)) {
            $this->ensureMethod(static::METHOD_GET);
            $this->ensureAccess(Role::getRoles());
            return $this->processGetOrderById($factory, (int)$matches[1]);
        }

        if ($url === 'orders/new') {
            $this->ensureMethod(static::METHOD_POST);
            $token = $this->ensureAccess([ Role::ROLE_ADMIN, Role::ROLE_CASHIER ]);
            return $this->processOrderNew($factory, $token, $this->getOrderProcess($token));
        }

        if ($url === 'orders/cancel') {
            $this->ensureMethod(static::METHOD_POST);
            $token = $this->ensureAccess([ Role::ROLE_ADMIN, Role::ROLE_CASHIER ]);
            return $this->processOrderCancel($factory, $token, $this->getOrderProcess($token));
        }

        if ($url === 'orders/current'){
            $this->ensureMethod(static::METHOD_GET);
            $token = $this->ensureAccess([ Role::ROLE_ADMIN, Role::ROLE_CASHIER ]);
            return $this->processOrderIntrospection($this->getOrderProcess($token));
        }

        if ($url === 'orders/add-product-to-order') {
            $this->ensureMethod(static::METHOD_POST);
            $token = $this->ensureAccess([ Role::ROLE_ADMIN, Role::ROLE_CASHIER ]);
            $body = $this->getBody();
            return $this->processAddProductToOrder(
                $factory,
                $this->getOrderProcess($token),
                (string)$this->getParameter('refcode', $body),
                (int)$this->getParameter('quantity', $body)
            );
        }

        if ($url === 'orders/print-receipt') {
            $this->ensureMethod(static::METHOD_POST);
            $token = $this->ensureAccess([ Role::ROLE_ADMIN, Role::ROLE_CASHIER ]);
            return $this->processPrintReceipt(
                $factory,
                $this->getOrderProcess($token),
                $token
            );
        }

        if ($url === 'orders/change-quantity') {
            $this->ensureMethod(static::METHOD_POST);
            $token = $this->ensureAccess([ Role::ROLE_ADMIN, Role::ROLE_CASHIER ]);
            $body = $this->getBody();
            return $this->processChangeProductQuantity(
                $factory,
                $this->getOrderProcess($token),
                (string)$this->getParameter('refcode', $body),
                (int)$this->getParameter('quantity', $body)
            );
        }

        if ($url === 'orders/delete-product-from-order') {
            $this->ensureMethod(static::METHOD_POST);
            $token = $this->ensureAccess([ Role::ROLE_ADMIN, Role::ROLE_CASHIER ]);
            $body = $this->getBody();
            return $this->processDeleteProductFromOrder(
                $factory,
                $this->getOrderProcess($token),
                (string)$this->getParameter('refcode', $body)
            );
        }

        if ($url === 'orders/apply-discount') {
            $this->ensureMethod(static::METHOD_POST);
            $token = $this->ensureAccess([ Role::ROLE_ADMIN, Role::ROLE_CASHIER ]);
            $body = $this->getBody();
            return $this->processApplyDiscount(
                $factory,
                $token->getEmployee(),
                $this->getOrderProcess($token),
                $this->toDiscountType((string)$this->getParameter('discount_type', $body), 'discount_type'),
                Tools::parseNumber($this->getParameter('value', $body))
            );
        }

        if ($url === 'checkout') {
            $this->ensureMethod(static::METHOD_POST);
            $token = $this->ensureAccess([ Role::ROLE_ADMIN, Role::ROLE_CASHIER ]);
            $body = $this->getBody();
            return $this->processCheckout(
                $factory,
                $this->getOrderProcess($token),
                $this->toPaymentMethod((string)$this->getParameter('paymentMethod', $body), 'paymentMethod'),
            );
        }

        if ($url === 'payment/cancel') {
            $this->ensureMethod(static::METHOD_POST);
            $token = $this->ensureAccess([ Role::ROLE_ADMIN, Role::ROLE_CASHIER ]);
            return $this->processPaymentCancel(
                $factory,
                $this->getOrderProcess($token)
            );
        }

        if ($url === 'payment/cash') {
            $this->ensureMethod(static::METHOD_POST);
            $token = $this->ensureAccess([ Role::ROLE_ADMIN, Role::ROLE_CASHIER ]);
            $body = $this->getBody();
            return $this->processPaymentCash(
                $factory,
                $this->getOrderProcess($token),
                Tools::parseNumber($this->getParameter('amount', $body)),
                $this->getWorkstation($factory, $token),
            );
        }

        if ($url === 'payment/card') {
            $this->ensureMethod(static::METHOD_POST);
            $token = $this->ensureAccess([ Role::ROLE_ADMIN, Role::ROLE_CASHIER ]);
            $body = $this->getBody();
            return $this->processPaymentCardOffline(
                $factory,
                $this->getOrderProcess($token),
                Tools::parseNumber($this->getParameter('amount', $body)),
                $this->getWorkstation($factory, $token),
            );
        }

        if ($url === 'payment/await') {
            $this->ensureMethod(static::METHOD_GET);
            $token = $this->ensureAccess([ Role::ROLE_ADMIN, Role::ROLE_CASHIER ]);
            return $this->processAwaitPayment(
                $factory,
                $this->getOrderProcess($token)
            );
        }

        return new BadRequestResponse("Invalid request URI: {$url}", 404);
    }

    /**
     * @param string $method
     * @return void
     * @throws InvalidRequestException
     */
    protected function ensureMethod(string $method)
    {
        $usedMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($usedMethod !== $method) {
            throw new InvalidRequestException("Invalid request method: $usedMethod", 405);
        }
    }

    /**
     * @param string[] $allowedRoles
     * @return Token
     *
     * @throws AccessDeniedException
     * @throws PrestaShopException
     * @throws ForbiddenException
     */
    protected function ensureAccess(array $allowedRoles): Token
    {
        $token = $this->getTokenFromAuthHeader();

        $allowedRoles[] = Role::ROLE_ADMIN;
        $allowedRoles = array_unique($allowedRoles);
        if (! in_array($token->getRole(), $allowedRoles)) {
            throw new ForbiddenException("To access this resource you need to have one of following roles: [".implode(', ', $allowedRoles)."]");
        }

        $context = Context::getContext();
        $context->employee = $token->getEmployee();
        $languageId = (int)$context->employee->id_lang;
        $context->cookie->id_lang = $languageId;
        if ((int)$context->language->id_lang !== $languageId) {
            $context->language = new Language($languageId);
        }
        return $token;
    }

    /**
     * @param Token $token
     * @return OrderProcess
     * @throws PrestaShopException
     */
    protected function getOrderProcess(Token $token): OrderProcess
    {
        $service = $this->module->getFactory()->getOrderProcessService();
        $orderProcess = $service->getFromToken($token);

        // update context
        $cart = $orderProcess->getCart();
        $context = Context::getContext();
        $context->cart = $cart;

        return $orderProcess;
    }

    /**
     * @param string $name
     * @param array $data
     * @param bool $required
     * @param mixed $default
     * @return mixed
     * @throws InvalidRequestException
     */
    protected function getParameter($name, $data, $required = true, $default = null)
    {
        if (array_key_exists($name, $data)) {
            return $data[$name];
        }
        if ($required) {
            throw new InvalidRequestException("Missing required parameter '$name'");
        }
        return $default;
    }

    /**
     * @return mixed
     * @throws InvalidRequestException
     */
    protected function getBody()
    {
        $content = file_get_contents('php://input');
        if (! $content) {
            throw new InvalidRequestException("Request contains empty body");
        }
        if (trim($content) === 'null') {
            return null;
        }
        $data = json_decode($content, true);
        if (! is_null($data)) {
            return $data;
        }
        throw new InvalidRequestException("Failed to parse request data: " . json_last_error_msg());
    }

    /**
     * @param int $responseCode
     * @return void
     */
    protected function setResponseCode(int $responseCode)
    {
        http_response_code($responseCode);
    }

    /**
     * @param Factory $factory
     * @param string $type
     * @param string $term
     *
     * @return GetSkuListResponse
     */
    protected function processGetProducts(Factory $factory, string $type = SkuService::SEARCH_ALL, string $term = ''): GetSkuListResponse
    {

        $list = $factory->getSKUService()->find($type, $term);
        return new GetSkuListResponse($list);
    }


    /**
     * @param Factory $factory
     * @param int $productId
     * @param int $combinationId
     *
     * @return SkuResponse|NotFoundResponse
     */
    private function processGetProductById(Factory $factory, int $productId, int $combinationId): SkuResponse|NotFoundResponse
    {
        try {
            $sku = $factory->getSKUService()->getById($productId, $combinationId);
            return new SkuResponse($sku);
        } catch (NotFoundException $e) {
            return new NotFoundResponse($e->getMessage());
        }
    }


    /**
     * @param Factory $factory
     * @param string $reference
     *
     * @return SkuResponse|NotFoundResponse
     */
    private function processGetProductByReference(Factory $factory, string $reference):  SkuResponse|NotFoundResponse
    {
        $sku = $factory->getSKUService()->findByReference($reference);
        if (! $sku) {
            return new NotFoundResponse("SKU with reference code '$reference' not found");
        }
        return new SkuResponse($sku);
    }

    /**
     * @param Factory $factory
     * @param OrderProcess $orderProcess
     * @param string $refcode
     * @param int $quantity
     *
     * @return OrderProcessResponse|NotFoundResponse|BadRequestResponse|OutOfStockResponse|MinimalQuantityRequiredResponse
     * @throws PrestaShopException
     */
    private function processAddProductToOrder(Factory $factory, OrderProcess $orderProcess, string $refcode, int $quantity)
        : OrderProcessResponse
        | NotFoundResponse
        | BadRequestResponse
        | OutOfStockResponse
        | MinimalQuantityRequiredResponse
    {
        if (! $orderProcess->canBeModified()) {
            return new BadRequestResponse("Order process can't be modified. Current status: " . $orderProcess->getStatus());
        }
        $sku = $factory->getSKUService()->findByReference($refcode);
        if (! $sku) {
            return new NotFoundResponse("SKU with reference code '$refcode' not found");
        }
        $currentQuantity = 0;
        $cart = $orderProcess->getCart();
        foreach ($cart->getProducts() as $item) {
            if ((int)$item['id_product'] === $sku->productId && (int)$item['id_product_attribute'] === $sku->combinationId) {
                $currentQuantity += (int)$item['quantity'];
            }
        }
        $newQuantity = $currentQuantity + $quantity;
        return $this->processChangeProductQuantity($factory, $orderProcess, $refcode, $newQuantity);
    }

    /**
     * @param Factory $factory
     * @param OrderProcess $orderProcess
     * @param string $refcode
     * @param int $quantity
     *
     *
     * @return OrderProcessResponse|NotFoundResponse|BadRequestResponse|OutOfStockResponse|MinimalQuantityRequiredResponse
     *
     * @throws PrestaShopException
     */
    private function processChangeProductQuantity(Factory $factory, OrderProcess $orderProcess, string $refcode, int $quantity)
        : OrderProcessResponse
        | NotFoundResponse
        | BadRequestResponse
        | OutOfStockResponse
        | MinimalQuantityRequiredResponse
    {
        if (! $orderProcess->canBeModified()) {
           return new BadRequestResponse("Order process can't be modified. Current status: " . $orderProcess->getStatus());
        }
        $sku = $factory->getSKUService()->findByReference($refcode);
        if (! $sku) {
            return new NotFoundResponse("SKU with reference code '$refcode' not found");
        }

        if ($quantity < 0) {
            return new BadRequestResponse("Quantity can't be negative");
        }

        $cart = $orderProcess->getCart();
        $currentQuantity = 0;
        foreach ($cart->getProducts() as $item) {
            if ((int)$item['id_product'] === $sku->productId && (int)$item['id_product_attribute'] === $sku->combinationId) {
                $currentQuantity += (int)$item['quantity'];
            }
        }

        $diff = $quantity - $currentQuantity;

        if ($diff > 0) {
            $result = $cart->updateQty($diff, $sku->productId, $sku->combinationId, 0, 'up', $cart->id_address_delivery);
        } elseif ($diff < 0) {
            $result = $cart->updateQty(abs($diff), $sku->productId, $sku->combinationId, 0, 'down', $cart->id_address_delivery);
        } else {
            $result = true;
        }

        if (! $result) {
            return new OutOfStockResponse($sku, StockAvailable::getQuantityAvailableByProduct($sku->productId, $sku->combinationId));
        } elseif ($result < 0) {
            if ($sku->combinationId) {
                $minimalQty = ProductAttribute::getAttributeMinimalQty($sku->combinationId);
            } else {
                $product = new Product($sku->productId);
                $minimalQty = $product->minimal_quantity;
            }
            return new MinimalQuantityRequiredResponse($sku, $minimalQty);
        }
        return new OrderProcessResponse($orderProcess);
    }

    /**
     * @param Factory $factory
     * @param OrderProcess $orderProcess
     * @param string $refcode
     *
     * @return OrderProcessResponse|NotFoundResponse|BadRequestResponse
     *
     * @throws OrderProcessResponse
     * @throws PrestaShopException
     */
    private function processDeleteProductFromOrder(Factory $factory, OrderProcess $orderProcess, string $refcode)
        : OrderProcessResponse
        | NotFoundResponse
        | BadRequestResponse
    {
        if (! $orderProcess->canBeModified()) {
            return new BadRequestResponse("Order process can't be modified. Current status: " . $orderProcess->getStatus());
        }
        $sku = $factory->getSKUService()->findByReference($refcode);
        if (! $sku) {
            return new NotFoundResponse("SKU with reference code '$refcode' not found");
        }
        $cart = $orderProcess->getCart();
        $quantity = 0;
        foreach ($cart->getProducts() as $item) {
            if ((int)$item['id_product'] === $sku->productId && (int)$item['id_product_attribute'] === $sku->combinationId) {
                $quantity += (int)$item['quantity'];
            }
        }
        if ($quantity > 0) {
            $cart->updateQty($quantity, $sku->productId, $sku->combinationId, 0, 'down', $cart->id_address_delivery);
        }
        return new OrderProcessResponse($orderProcess);
    }

    /**
     * @param OrderProcess $orderProcess
     *
     * @return OrderProcessResponse
     */
    private function processOrderIntrospection(OrderProcess $orderProcess): OrderProcessResponse
    {
        return new OrderProcessResponse($orderProcess);
    }

    /**
     * @param Factory $factory
     * @param int $workstationId
     * @param string $username
     * @param string $password
     * @param string $role
     *
     * @return UserResponse|NotFoundResponse
     *
     * @throws AccessDeniedException
     * @throws PrestaShopException
     */
    protected function processLogin(
        Factory $factory,
        int $workstationId,
        string $username,
        string $password,
        string $role
    ) : UserResponse
      | NotFoundResponse
    {
        $workstationService = $factory->getWorkstationService();
        $workstation = $workstationService->findById($workstationId);
        if (! $workstation) {
            return new NotFoundResponse("Workstation with id '$workstationId' not found");
        }
        $user = $factory->authService()->login($username, $password, $role, $workstation);
        return new UserResponse($user);
    }

    /**
     * @param Factory $factory
     * @param Token $token
     *
     * @return JSendResponse
     * @throws PrestaShopException
     */
    private function processTokenIntrospection(Factory $factory, Token $token): JSendResponse
    {
        $user = $factory->authService()->tokenIntrospection($token);
        return new UserResponse($user);
    }

    /**
     * @param Factory $factory
     * @param Employee $employee
     * @param OrderProcess $orderProcess
     * @param string $discountType
     * @param float $value
     *
     * @return OrderProcessResponse|BadRequestResponse
     *
     * @throws PrestaShopException
     */
    private function processApplyDiscount(Factory $factory, Employee $employee, OrderProcess $orderProcess, string $discountType, float $value)
        : OrderProcessResponse
        | BadRequestResponse
    {
        if (! $orderProcess->canBeModified()) {
            return new BadRequestResponse("Order process can't be modified. Current status: " . $orderProcess->getStatus());
        }

        $cart = $orderProcess->getCart();
        $cartId = (int)$cart->id;
        $orderLevelPrefix = "POS_{$cartId}_";
        $cartRule = $this->findOrderDiscountCartRule($cart->getCartRules(), $orderLevelPrefix);
        if (! $cartRule) {
            $cartRule = new CartRule();
            $cartRule->code = $orderLevelPrefix . strtoupper(Tools::passwdGen(24));
        } else {
            $cart->removeCartRule((int)$cartRule->id);
        }

        $employeeID = (int)$employee->id;

        $languageIds = Language::getIDs(false);
        $cartRule->name = [];
        foreach ($languageIds as $idLang) {
            $cartRule->name[$idLang] = "POS: order level discount";
        }

        $cartRule->description = "Discount applied by ".$employee->firstname . ' ' . $employee->lastname." [$employeeID] for cart $cartId";
        $cartRule->quantity = 1;
        $cartRule->highlight = 0;
        $cartRule->active = 1;
        $cartRule->quantity_per_user = 1;
        $cartRule->date_from = date('Y-m-d 00:00:00');
        $cartRule->date_to = date('Y-m-d 23:59:59', strtotime('+1 days'));
        $cartRule->partial_use = false;
        if ($discountType === static::DISCOUNT_PERCENTAGE) {
            $cartRule->reduction_percent = max(0, min(100, $value));
            $cartRule->reduction_amount = 0;
        } else {
            $cartRule->reduction_percent = 0;
            $cartRule->reduction_amount = abs($value);
            $cartRule->reduction_currency = $cart->id_currency;
            $cartRule->reduction_tax = true;
        }
        if (! $cart->id) {
            $cart->save();
        }
        $cartRule->save();
        $cart->addCartRule((int)$cartRule->id);
        return new OrderProcessResponse($orderProcess);
    }

    /**
     * @param Factory $factory
     * @param OrderProcess $orderProcess
     * @param PaymentMethod $paymentMethod
     *
     * @return OrderProcessResponse|BadRequestResponse
     *
     * @throws PrestaShopException
     */
    private function processCheckout(Factory $factory, OrderProcess $orderProcess, PaymentMethod $paymentMethod)
        : OrderProcessResponse
        | BadRequestResponse
    {
        if (! $orderProcess->canTransitionTo(OrderProcess::STATUS_PROCESSING_PAYMENT)) {
            return new BadRequestResponse("Can't start checkout process, current status: " . $orderProcess->getStatus());
        }

        if (! $orderProcess->getCart()->getProducts()) {
            return new BadRequestResponse("Empty cart");
        }

        if (! $this->checkPaymentMethodAvailable($factory, $orderProcess, $paymentMethod)) {
            return new BadRequestResponse("Payment method ".$paymentMethod->getId()." is not allowed");
        }

        $orderProcessService = $factory->getOrderProcessService();

        $orderProcess = $orderProcessService->startPayment($orderProcess, $paymentMethod);
        return new OrderProcessResponse($orderProcess);
    }

    /**
     * @param Factory $factory
     * @param OrderProcess $orderProcess
     *
     * @return OrderProcessResponse|InvalidOrderStatusResponse
     *
     * @throws PrestaShopException
     */
    private function processPaymentCancel(Factory $factory, OrderProcess $orderProcess)
        : InvalidOrderStatusResponse
        | OrderProcessResponse
    {
        if ($orderProcess->getStatus() !== OrderProcess::STATUS_PROCESSING_PAYMENT) {
            return new InvalidOrderStatusResponse(OrderProcess::STATUS_PROCESSING_PAYMENT, $orderProcess->getStatus());
        }
        $orderProcessService = $this->module->getFactory()->getOrderProcessService();
        $orderProcess = $orderProcessService->cancelPayment($orderProcess);
        return new OrderProcessResponse($orderProcess);
    }


    /**
     * @param Factory $factory
     * @param OrderProcess $orderProcess
     * @param float $amount
     * @param Workstation $workstation
     *
     * @return InvalidAmountCollectedResponse|BadRequestResponse
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function processPaymentCash(
        Factory $factory,
        OrderProcess $orderProcess,
        float $amount,
        Workstation $workstation
    )
        : InvalidAmountCollectedResponse
        | BadRequestResponse
        | OrderProcessResponse
    {
        $paymentMethod = $orderProcess->getPaymentMethod();
        if (! $paymentMethod) {
            return new BadRequestResponse("Can't process cash payment. Payment method was not selected yet");
        }
        if ($paymentMethod->getId() !== CashPaymentMethod::TYPE) {
            return new BadRequestResponse("Can't process cash payment. Selected payment method is " . $paymentMethod->getId());
        }

        if (! $orderProcess->canTransitionTo(OrderProcess::STATUS_COMPLETED)) {
            return new BadRequestResponse("Can't process payment. Current status: " . $orderProcess->getStatus());
        }

        $amount = Tools::roundPrice($amount);
        $total = Tools::roundPrice($orderProcess->getCart()->getOrderTotal());
        if ($amount !== $total) {
            return new InvalidAmountCollectedResponse($amount, $total);
        }
        $orderProcessService = $this->module->getFactory()->getOrderProcessService();
        $orderProcess = $orderProcessService->acceptPayment(
            $orderProcess,
            $paymentMethod,
            $amount,
            [],
            $workstation
        );
        return new OrderProcessResponse($orderProcess);
    }

    /**
     * @param Factory $factory
     * @param OrderProcess $orderProcess
     * @param float $amount
     * @param Workstation $workstation
     *
     * @return InvalidAmountCollectedResponse|BadRequestResponse
     *
     * @throws PrestaShopException
     */
    private function processPaymentCardOffline(
        Factory $factory,
        OrderProcess $orderProcess,
        float $amount,
        Workstation $workstation
    ) : InvalidAmountCollectedResponse
       | BadRequestResponse
       | OrderProcessResponse
    {
        $paymentMethod = $orderProcess->getPaymentMethod();
        if (! $paymentMethod) {
            return new BadRequestResponse("Can't process offline card payment. Payment method was not selected yet");
        }
        if ($paymentMethod->getId() !== CreditCardOfflinePaymentMethod::TYPE) {
            return new BadRequestResponse("Can't process offline card payment. Selected payment method is " . $paymentMethod->getId());
        }

        if (! $orderProcess->canTransitionTo(OrderProcess::STATUS_COMPLETED)) {
            return new BadRequestResponse("Can't process offline card payment. Current status: " . $orderProcess->getStatus());
        }

        $amount = Tools::roundPrice($amount);
        $total = Tools::roundPrice($orderProcess->getCart()->getOrderTotal());
        if ($amount !== $total) {
            return new InvalidAmountCollectedResponse($amount, $total);
        }
        $orderProcessService = $this->module->getFactory()->getOrderProcessService();
        $orderProcess = $orderProcessService->acceptPayment(
            $orderProcess,
            $paymentMethod,
            $amount,
            [],
            $workstation
        );
        return new OrderProcessResponse($orderProcess);
    }

    /**
     * @param Factory $factory
     * @param OrderProcess $orderProcess
     *
     * @return OrderProcessResponse
     */
    private function processAwaitPayment(Factory $factory, OrderProcess $orderProcess) : OrderProcessResponse
    {
        return new OrderProcessResponse($orderProcess);
    }

    /**
     * @param array $cartRules
     * @param string $orderCartRulePrefix
     * @return CartRule|null
     */
    private function findOrderDiscountCartRule(array $cartRules, string $orderCartRulePrefix): ?CartRule
    {
        foreach ($cartRules as $row) {
            /** @var CartRule $cartRule */
            $cartRule = $row['obj'];
            // TODO: check id_customer
            if (strpos($cartRule->code, $orderCartRulePrefix) === 0) {
                return $cartRule;
            }
        }
        return null;
    }

    /**
     * @param string $param
     * @param string $paramName
     *
     * @return string
     *
     * @throws InvalidRequestException
     */
    private function toDiscountType(string $param, string $paramName): string
    {
        switch (strtolower($param)) {
            case static::DISCOUNT_PERCENTAGE:
                return static::DISCOUNT_PERCENTAGE;
            case static::DISCOUNT_AMOUNT:
                return static::DISCOUNT_AMOUNT;
        }
        throw new InvalidRequestException("Invalid value for parameter '$paramName'");
    }

    /**
     * @param string $param
     * @param string $paramName
     *
     * @return PaymentMethod
     *
     * @throws InvalidRequestException
     * @throws PrestaShopException
     */
    private function toPaymentMethod(string $param, string $paramName): PaymentMethod
    {
        $methods = $this->module->getFactory()->getPaymentMethods();
        $method = $methods->findMethod($param);
        if (! $method) {
            throw new InvalidRequestException("Invalid value for parameter '$paramName'");
        }
        return $method;
    }

    /**
     * @return void
     *
     * @throws PrestaShopException
     */
    private function initContext()
    {
        $context = Context::getContext();
        if (! $context->cart) {
            $context->cart = new Cart();
        }
        if (! $context->currency) {
            $context->currency = Currency::getCurrencyInstance(Configuration::get('PS_CURRENCY_DEFAULT'));
        }
    }


    /**
     * @return Token
     *
     * @throws AccessDeniedException
     * @throws PrestaShopException
     */
    private function getTokenFromAuthHeader(): Token
    {
        $headerValue = trim($_SERVER['HTTP_AUTHORIZATION'] ?? '');
        if (! $headerValue) {
            throw new AccessDeniedException("Token required");
        }

        $tokenValue = preg_replace("/^Bearer +/i", "", $headerValue);
        if (!$tokenValue) {
            throw new AccessDeniedException("Token value required");
        }

        $token = $this->module->getFactory()->authService()->findToken($tokenValue);
        if (! $token) {
            throw new AccessDeniedException("Invalid token");
        }
        return $token;
    }

    /**
     * @param Factory $factory
     * @param Token $token
     * @param OrderProcess $orderProcess
     * @return OrderProcessResponse
     * @throws PrestaShopException
     */
    private function processOrderNew(Factory $factory, Token $token ,OrderProcess $orderProcess)
        : BadRequestResponse
        | OrderProcessResponse
    {
        if ($orderProcess->getStatus() === OrderProcess::STATUS_COMPLETED) {
            $orderProcessService = $factory->getOrderProcessService();
            return new OrderProcessResponse($orderProcessService->createOrderProcess($token));
        }
        return new BadRequestResponse("Cant create new order process, current process is not completed. Status: " . $orderProcess->getStatus());
    }

    /**
     * @param Factory $factory
     * @param Token $token
     * @param OrderProcess $orderProcess
     * @return OrderProcessResponse
     * @throws PrestaShopException
     */
    private function processOrderCancel(Factory $factory, Token $token , OrderProcess $orderProcess)
        : BadRequestResponse
        | OrderProcessResponse
    {
        if ($orderProcess->canTransitionTo(OrderProcess::STATUS_CANCELED)) {
            $orderProcessService = $factory->getOrderProcessService();
            $orderProcessService->changeStatus($orderProcess, OrderProcess::STATUS_CANCELED);
            return new OrderProcessResponse($orderProcessService->createOrderProcess($token));
        }
        return new BadRequestResponse("Can't cancel order process. Status: " . $orderProcess->getStatus());
    }

    /**
     * @param Factory $factory
     * @param OrderProcess $orderProcess
     * @param Token $token
     * @return InvalidOrderStatusResponse|PrintReceiptResponse|BadRequestResponse|ServerErrorException
     *
     * @throws PrestaShopException
     */
    private function processPrintReceipt(Factory $factory, OrderProcess $orderProcess, Token $token)
        : InvalidOrderStatusResponse
        | PrintReceiptResponse
        | BadRequestResponse
        | ServerErrorException
    {
        if ($orderProcess->getStatus() === OrderProcess::STATUS_COMPLETED) {
            $workstation = $factory->getWorkstationService()->findById($token->getWorkstationId());
            if (! $workstation) {
                return new ServerErrorException("Workstation not found");
            }
            $printerId = $workstation->getReceiptPrinterId();
            if (! $printerId) {
                return new BadRequestResponse("Workstation has no receipt printer assigned");
            }
            $printnodeIntegration = $factory->getPrintnodeIntegration();
            if (! $printnodeIntegration->isEnabled()) {
                return new BadRequestResponse("Printnode integration is not enabled");
            }

            $printService = $printnodeIntegration->getService();
            try {
                $printJob = $printService->print(
                    'tbpos:receipt',
                    (int)$orderProcess->getOrder()->id,
                    $printerId,
                    (int)Context::getContext()->shop->id,
                    (int)Context::getContext()->language->id
                );
            } catch (Throwable $e) {
                return new ServerErrorException($e->getMessage());

            }

            return new PrintReceiptResponse($printerId, $printJob);
        } else {
            return new InvalidOrderStatusResponse(OrderProcess::STATUS_COMPLETED, $orderProcess->getStatus());
        }
    }

    /**
     * @param Factory $factory
     * @param OrderProcess $orderProcess
     * @param PaymentMethod $paymentMethod
     * @return bool
     */
    public function checkPaymentMethodAvailable(Factory $factory, OrderProcess $orderProcess, PaymentMethod $paymentMethod): bool
    {
        $availableMethods = $factory->getPaymentMethods()->getMethodsAvailableForOrderProcess($orderProcess);

        foreach ($availableMethods as $method) {
            if ($method->getId() === $paymentMethod->getId()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param Factory $factory
     *
     * @return GetWorkstationListResponse
     *
     * @throws PrestaShopException
     */
    private function processGetWorkstations(Factory $factory): GetWorkstationListResponse
    {
        return new GetWorkstationListResponse($factory->getWorkstationService()->findAll(true));
    }

    /**
     * @throws PrestaShopException
     */
    private function processExchangeToken(Factory $factory, Token $token) : UserResponse
    {
        $authService = $factory->authService();
        $newToken = $authService->exchangeToken($token);
        $user = $authService->tokenIntrospection($newToken);
        return new UserResponse($user);
    }

    /**
     * @param string $value
     * @param string $paramName
     * @return string
     * @throws InvalidRequestException
     */
    private function toSearchType(string $value, $paramName): string
    {
        $values = [
            SkuService::SEARCH_ALL,
            SkuService::SEARCH_BARCODE,
            SkuService::SEARCH_NAME,
            SkuService::SEARCH_REFERENCE,
        ];
        if (in_array($value, $values)) {
            return $value;
        }
        throw new InvalidRequestException("Invalid value '$value' for parameter '$paramName', allowed values: [".implode(', ', $values)."]");
    }

    /**
     * @param Factory $factory
     * @param string $search
     * @param int $page
     * @param int $pageSize
     *
     * @return OrderListResponse
     *
     * @throws PrestaShopException
     */
    private function processListOrders(
        Factory $factory,
        string $search,
        int $page,
        int $pageSize
    ) : OrderListResponse
    {
        $pageSize = min(max(1, (int)$pageSize), 100);
        $page = max(1, (int)$page);
        $sql = (new DbQuery())
            ->select('o.*')
            ->from('orders', 'o')
            ->leftJoin('customer', 'c', 'c.id_customer = o.id_customer')
            ->limit($pageSize, ($page - 1) * $pageSize)
            ->orderBy('o.id_order DESC');

        $totalSql = (new DbQuery())
            ->select('COUNT(1) AS cnt')
            ->leftJoin('customer', 'c', 'c.id_customer = o.id_customer')
            ->from('orders', 'o');

        $search = trim((string)$search);
        if ($search !== '') {
            $where = [
                'o.reference LIKE \'%' . pSQL($search) . '%\'',
                'o.id_order LIKE \'%' . pSQL($search) . '%\'',
                'CONCAT(c.firstname, " ", c.lastname) LIKE \'%' . pSQL($search) . '%\'',
                'c.email LIKE \'%' . pSQL($search) . '%\'',
            ];
            $sql->where(implode(' OR ', $where));
            $totalSql->where(implode(' OR ', $where));
        }

        $conn = Db::readOnly();

        $results = $conn->getArray($sql);
        $orders = ObjectModel::hydrateCollection(Order::class, $results, Context::getContext()->language->id);

        $total = $conn->getValue($totalSql);

        return new OrderListResponse(
            $orders,
            $page,
            $pageSize,
            $total,
            $search
        );
    }

    /**
     * @param Factory $factory
     * @param int $orderId
     *
     * @return OrderResponse|NotFoundResponse
     * @throws PrestaShopException
     */
    private function processGetOrderById(
        Factory $factory,
        int $orderId
    ):OrderResponse
     |NotFoundResponse
    {
        $order = new Order($orderId);
        if (Validate::isLoadedObject($order)) {
            return new OrderResponse($order);
        } else {
            return new NotFoundResponse("Order with id $orderId not found");
        }
    }

    /**
     * @param Factory $factory
     * @param Token $token
     * @return Workstation
     *
     * @throws PrestaShopException
     */
    private function getWorkstation(Factory $factory, Token $token): Workstation
    {
        try {
            return $factory->getWorkstationService()->getById($token->getWorkstationId());
        } catch (NotFoundException $e) {
            throw new ServerErrorException("Failed to find workstation for token", 0, $e);
        }
    }

}
