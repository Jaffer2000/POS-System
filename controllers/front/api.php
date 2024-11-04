<?php

use Thirtybees\Core\DependencyInjection\ServiceLocatorCore;
use Thirtybees\Core\Error\ErrorUtils;
use Thirtybees\Module\POS\Api\Response\AccessDeniedResponse;
use Thirtybees\Module\POS\Api\Response\BadRequestResponse;
use Thirtybees\Module\POS\Api\Response\ForbiddenResponse;
use Thirtybees\Module\POS\Api\Response\GetSkuListResponse;
use Thirtybees\Module\POS\Api\Response\JSendErrorResponse;
use Thirtybees\Module\POS\Api\Response\JSendResponse;
use Thirtybees\Module\POS\Api\Response\MinimalQuantityRequiredResponse;
use Thirtybees\Module\POS\Api\Response\NotFoundResponse;
use Thirtybees\Module\POS\Api\Response\OrderResponse;
use Thirtybees\Module\POS\Api\Response\OutOfStockResponse;
use Thirtybees\Module\POS\Api\Response\SkuResponse;
use Thirtybees\Module\POS\Api\Response\UserResponse;
use Thirtybees\Module\POS\Auth\Model\Role;
use Thirtybees\Module\POS\Auth\Model\Token;
use Thirtybees\Module\POS\DependencyInjection\Factory;
use Thirtybees\Module\POS\Exception\AccessDeniedException;
use Thirtybees\Module\POS\Exception\ForbiddenException;
use Thirtybees\Module\POS\Exception\InvalidRequestException;
use Thirtybees\Module\POS\Exception\NotFoundException;

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
            return $this->processGetProducts($factory);
        }
        if (preg_match('#^products/([0-9]+)/([0-9]+)$#', $url, $matches)) {
            $this->ensureMethod(static::METHOD_GET);
            return $this->processGetProductById($factory, (int)$matches[1], (int)$matches[2]);
        }

        if (preg_match('#^products/(.*)$#', $url, $matches)) {
            $this->ensureMethod(static::METHOD_GET);
            return $this->processGetProductByReference($factory, $matches[1]);
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
                (string)$this->getParameter('username', $body),
                (string)$this->getParameter('password', $body),
                (string)$this->getParameter('role', $body),
            );
        }

        if ($url === 'orders/current'){
            $this->ensureMethod(static::METHOD_GET);
            $token = $this->ensureAccess([ Role::ROLE_ADMIN, Role::ROLE_CASHIER ]);
            return $this->processOrderIntrospection($this->getCart($token));
        }

        if ($url === 'orders/add-product-to-order') {
            $this->ensureMethod(static::METHOD_POST);
            $token = $this->ensureAccess([ Role::ROLE_ADMIN, Role::ROLE_CASHIER ]);
            $body = $this->getBody();
            return $this->processAddProductToOrder(
                $factory,
                $this->getCart($token),
                (string)$this->getParameter('refcode', $body),
                (int)$this->getParameter('quantity', $body)
            );
        }

        if ($url === 'orders/change-quantity') {
            $this->ensureMethod(static::METHOD_POST);
            $token = $this->ensureAccess([ Role::ROLE_ADMIN, Role::ROLE_CASHIER ]);
            $body = $this->getBody();
            return $this->processChangeProductQuantity(
                $factory,
                $this->getCart($token),
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
                $this->getCart($token),
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
                $this->getCart($token),
                $this->toDiscountType((string)$this->getParameter('discount_type', $body), 'discount_type'),
                Tools::parseNumber($this->getParameter('value', $body))
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
        $value = trim($_SERVER['HTTP_AUTHORIZATION'] ?? '');
        if (! $value) {
            throw new AccessDeniedException("Token required");
        }
        $token = Token::getFromAuthHeader($value);
        if (! $token) {
            throw new AccessDeniedException("Invalid token");
        }

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
     *
     * @return Cart
     *
     * @throws PrestaShopException
     */
    protected function getCart(Token $token): Cart
    {
        $context = Context::getContext();
        $cartId = $token->getCartId();
        $carrier = Carrier::getCarrierByReference((int)Configuration::get("TBPOS_CARRIER"));
        $carrierId = $carrier ? (int)$carrier->id : 0;

        if (! $cartId) {
            // TODO: extract
            $cart = new Cart();
            $cart->id_currency = (int)Configuration::get('PS_CURRENCY_DEFAULT');
            $cart->id_customer = 0;
            $cart->id_carrier = $carrierId;
            $cart->save();
            $token->updateCartId($cart->id);
            $context->cart = $cart;
        } else {
            if ((int)$context->cart->id !== $cartId) {
                $context->cart = new Cart($cartId);
            }

        }

        if ((int)$context->cart->id_carrier !== $carrierId) {
            $context->cart->id_carrier = $carrierId;
            $context->cart->save();
        }

        return $context->cart;
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
     *
     * @return GetSkuListResponse
     */
    protected function processGetProducts(Factory $factory): GetSkuListResponse
    {
        $list = $factory->getSKUService()->findAll();
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
        try {
            $sku = $factory->getSKUService()->getByReference($reference);
            return new SkuResponse($sku);
        } catch (NotFoundException $e) {
            return new NotFoundResponse($e->getMessage());
        }
    }

    /**
     * @param Factory $factory
     * @param Cart $cart
     * @param string $refcode
     * @param int $quantity
     *
     * @return OrderResponse|NotFoundResponse|BadRequestResponse|OutOfStockResponse|MinimalQuantityRequiredResponse
     * @throws PrestaShopException
     */
    private function processAddProductToOrder(Factory $factory, Cart $cart, string $refcode, int $quantity)
        : OrderResponse
        | NotFoundResponse
        | BadRequestResponse
        | OutOfStockResponse
        | MinimalQuantityRequiredResponse
    {
        $sku = $factory->getSKUService()->findByReference($refcode);
        if (! $sku) {
            return new NotFoundResponse("SKU with reference code '$refcode' not found");
        }
        $currentQuantity = 0;
        foreach ($cart->getProducts() as $item) {
            if ((int)$item['id_product'] === $sku->productId && (int)$item['id_combination'] === $sku->combinationId) {
                $currentQuantity += (int)$item['quantity'];
            }
        }
        $newQuantity = $currentQuantity + $quantity;
        return $this->processChangeProductQuantity($factory, $cart, $refcode, $newQuantity);
    }

    /**
     * @param Factory $factory
     * @param Cart $cart
     * @param string $refcode
     * @param int $quantity
     *
     *
     * @return OrderResponse|NotFoundResponse|BadRequestResponse|OutOfStockResponse|MinimalQuantityRequiredResponse
     *
     * @throws PrestaShopException
     */
    private function processChangeProductQuantity(Factory $factory, Cart $cart, string $refcode, int $quantity)
        : OrderResponse
        | NotFoundResponse
        | BadRequestResponse
        | OutOfStockResponse
        | MinimalQuantityRequiredResponse
    {
        $sku = $factory->getSKUService()->findByReference($refcode);
        if (! $sku) {
            return new NotFoundResponse("SKU with reference code '$refcode' not found");
        }

        if ($quantity < 0) {
            return new BadRequestResponse("Quantity can't be negative");
        }

        $currentQuantity = 0;
        foreach ($cart->getProducts() as $item) {
            if ((int)$item['id_product'] === $sku->productId && (int)$item['id_combination'] === $sku->combinationId) {
                $currentQuantity += (int)$item['quantity'];
            }
        }

        $diff = $quantity - $currentQuantity;

        if ($diff > 0) {
            $result = $cart->updateQty($diff, $sku->productId, $sku->combinationId, 0, 'up');
        } elseif ($diff < 0) {
            $result = $cart->updateQty(abs($diff), $sku->productId, $sku->combinationId, 0, 'down');
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
        return new OrderResponse($cart);
    }

    /**
     * @param Factory $factory
     * @param Cart $cart
     * @param string $refcode
     *
     * @return OrderResponse|NotFoundResponse
     *
     * @throws OrderResponse
     * @throws PrestaShopException
     */
    private function processDeleteProductFromOrder(Factory $factory, Cart $cart, string $refcode): OrderResponse|NotFoundResponse
    {
        try {
            $sku = $factory->getSKUService()->getByReference($refcode);
        } catch (NotFoundException $e) {
            return new NotFoundResponse($e->getMessage());
        }
        $quantity = 0;
        foreach ($cart->getProducts() as $item) {
            if ((int)$item['id_product'] === $sku->productId && (int)$item['id_combination'] === $sku->combinationId) {
                $quantity += (int)$item['quantity'];
            }
        }
        if ($quantity > 0) {
            $cart->updateQty($quantity, $sku->productId, $sku->combinationId, 0, 'down');
        }
        return new OrderResponse($cart);
    }

    /**
     * @param Cart $cart
     *
     * @return OrderResponse
     */
    private function processOrderIntrospection(Cart $cart): OrderResponse
    {
        return new OrderResponse($cart);
    }

    /**
     * @param Factory $factory
     * @param string $username
     * @param string $password
     * @param string $role
     *
     * @return UserResponse
     * @throws AccessDeniedException
     */
    protected function processLogin(
        Factory $factory,
        string $username,
        string $password,
        string $role
    ): UserResponse {
        $user = $factory->authService()->login($username, $password, $role);
        return new UserResponse($user);
    }

    /**
     * @param Factory $factory
     * @param Token $token
     *
     * @return JSendResponse
     */
    private function processTokenIntrospection(Factory $factory, Token $token): JSendResponse
    {
        $user = $factory->authService()->tokenIntrospection($token);
        return new UserResponse($user);
    }

    /**
     * @param Factory $factory
     * @param Employee $employee
     * @param Cart $cart
     * @param string $discountType
     * @param float $value
     *
     * @return OrderResponse
     *
     * @throws PrestaShopException
     */
    private function processApplyDiscount(Factory $factory, Employee $employee, Cart $cart, string $discountType, float $value): OrderResponse
    {
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
        return new OrderResponse($cart);
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


}
