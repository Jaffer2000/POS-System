<?php

use Thirtybees\Core\DependencyInjection\ServiceLocator;
use Thirtybees\Core\Error\ErrorUtils;
use Thirtybees\Core\Error\Response\JSendErrorResponse;
use Thirtybees\Module\POS\Api\Response\GetSkuListResponse;
use Thirtybees\Module\POS\Api\Response\OrderResponse;
use Thirtybees\Module\POS\Api\Response\SkuResponse;
use Thirtybees\Module\POS\Api\Response\Response;
use Thirtybees\Module\POS\Api\Response\UserResponse;
use Thirtybees\Module\POS\Auth\Model\Role;
use Thirtybees\Module\POS\Auth\Model\Token;
use Thirtybees\Module\POS\DependencyInjection\Factory;
use Thirtybees\Module\POS\Exception\AccessDeniedException;
use Thirtybees\Module\POS\Exception\InvalidRequestException;
use Thirtybees\Module\POS\Exception\NotAllowedException;
use Thirtybees\Module\POS\Exception\NotFoundException;
use Thirtybees\Module\POS\Exception\UnauthorizedException;

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

    protected $token;


    /**
     * @return void
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

        ServiceLocator::getInstance()->getErrorHandler()->setErrorResponseHandler(new JSendErrorResponse(_PS_MODE_DEV_));

        try {
            $factory = $this->module->getFactory();
            $response = $this->dispatch($factory, Tools::getValue('apiUrl'));
            $this->sendResponse($response->getResponse($factory));
        } catch (AccessDeniedException $e) {
            $this->sendFailResponse($e->getMessage(), 401);
        } catch (InvalidRequestException $e) {
            $this->sendFailResponse($e->getMessage(), 400);
        } catch (NotAllowedException $e) {
            $this->sendFailResponse($e->getMessage(), 403);
        } catch (NotFoundException $e) {
            $this->sendFailResponse($e->getMessage(), 404);
        } catch (UnauthorizedException $e) {
            $this->sendFailResponse($e->getMessage(), 403);
        } catch (Throwable $e) {
            $errorHandler = ServiceLocator::getInstance()->getErrorHandler();
            $errorHandler->logFatalError(ErrorUtils::describeException($e));
            $this->sendFailResponse($e->getMessage(), 500);
        }
    }

    /**
     * @param string $responseMessage
     * @param int $responseCode
     *
     * @return void
     */
    public function sendFailResponse(string $responseMessage, int $responseCode)
    {
        $this->sendResponse(['error' => $responseMessage], $responseCode, $responseMessage);
    }

    /**
     * @param array $payload
     */
    protected function sendResponse($payload, int $responseCode = 200, string $responseMessage = null)
    {
        if (! headers_sent()) {
            header('Content-Type: application/json');
            $this->setResponseCode($responseCode, $responseMessage);
        }
        die(json_encode($payload, JSON_PRETTY_PRINT));
    }

    /**
     * @param Factory $factory
     * @param string $url
     * @return Response
     *
     * @throws AccessDeniedException
     * @throws InvalidRequestException
     * @throws NotFoundException
     * @throws PrestaShopException
     * @throws UnauthorizedException
     */
    protected function dispatch(Factory $factory, string $url): Response
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
            $this->ensureAccess(Role::getRoles());
            return $this->processTokenIntrospection(
                $factory,
                $this->getToken()
            );
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
            $this->ensureAccess([ Role::ROLE_ADMIN, Role::ROLE_CASHIER ]);
            return $this->processOrderIntrospection($this->getCart($this->getToken()));
        }

        if ($url === 'orders/add-product-to-order') {
            $this->ensureMethod(static::METHOD_POST);
            $this->ensureAccess([ Role::ROLE_ADMIN, Role::ROLE_CASHIER ]);
            $body = $this->getBody();
            return $this->processAddProductToOrder(
                $factory,
                $this->getCart($this->getToken()),
                (string)$this->getParameter('refcode', $body),
                (int)$this->getParameter('quantity', $body)
            );
        }

        if ($url === 'orders/delete-product-from-order') {
            $this->ensureMethod(static::METHOD_POST);
            $this->ensureAccess([ Role::ROLE_ADMIN, Role::ROLE_CASHIER ]);
            $body = $this->getBody();
            return $this->processDeleteProductFromOrder(
                $factory,
                $this->getCart($this->getToken()),
                (string)$this->getParameter('refcode', $body)
            );
        }

        if ($url === 'orders/apply-discount') {
            $this->ensureMethod(static::METHOD_POST);
            $this->ensureAccess([ Role::ROLE_ADMIN, Role::ROLE_CASHIER ]);
            $body = $this->getBody();
            return $this->processApplyDiscount(
                $factory,
                $this->getCart($this->getToken()),
                $this->toDiscountType((string)$this->getParameter('discount_type', $body), 'discount_type'),
                Tools::parseNumber($this->getParameter('value', $body))
            );
        }

        throw new InvalidRequestException("Unknown action: '$url'", 404);
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
     * @return void
     * @throws AccessDeniedException
     * @throws UnauthorizedException
     * @throws PrestaShopException
     * @throws NotFoundException
     */
    protected function ensureAccess(array $allowedRoles)
    {
        $token = $this->getToken();
        $allowedRoles[] = Role::ROLE_ADMIN;
        if (! in_array($token->getRole(), $allowedRoles)) {
            throw new UnauthorizedException();
        }

        $context = Context::getContext();
        $context->employee = $token->getEmployee();
        $languageId = (int)$context->employee->id_lang;
        $context->cookie->id_lang = $languageId;
        if ((int)$context->language->id_lang !== $languageId) {
            $context->language = new Language($languageId);
        }
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
        if (! $cartId) {
            // TODO: extract
            $cart = new Cart();
            $cart->id_currency = (int)Configuration::get('PS_CURRENCY_DEFAULT');
            $cart->id_customer = 0;
            $cart->id_carrier = (int)Configuration::get('PS_CARRIER_DEFAULT');

            $cart->save();
            $token->updateCartId($cart->id);
            $context->cart = $cart;
        } else {
            if ((int)$context->cart->id !== $cartId) {
                $context->cart = new Cart($cartId);
            }
        }
        return $context->cart;
    }

    /**
     * @return Token
     * @throws PrestaShopException
     * @throws AccessDeniedException
     */
    protected function getToken(): Token
    {
        if ($this->token === null) {
            $value = trim($_SERVER['HTTP_AUTHORIZATION'] ?? '');
            if (! $value) {
                throw new AccessDeniedException("Token required");
            }
            $token = Token::getFromAuthHeader($value);
            if (! $token) {
                throw new AccessDeniedException("Invalid token");
            }
            $this->token = $token;
        }
        return $this->token;
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
     * @param string|null $responseMessage
     * @return void
     */
    protected function setResponseCode(int $responseCode, string $responseMessage = null)
    {
        if ($responseMessage) {
            $protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0';
            header($protocol . ' ' . $responseCode . ' ' . $responseMessage);
        } else {
            http_response_code($responseCode);
        }
    }

    /**
     * @param Factory $factory
     *
     * @return GetSkuListResponse
     */
    protected function processGetProducts(Factory $factory): GetSkuListResponse
    {
        $list = $factory->getSKUService()->findAll();
        $response = new GetSkuListResponse($list);
        return $response;
    }


    /**
     * @param Factory $factory
     * @param int $productId
     * @param int $combinationId
     * @return SkuResponse
     *
     * @throws NotFoundException
     */
    private function processGetProductById(Factory $factory, int $productId, int $combinationId): SkuResponse
    {
        $sku = $factory->getSKUService()->getById($productId, $combinationId);
        return new SkuResponse($sku);
    }

    /**
     * @param Factory $factory
     * @param string $reference
     * @return SkuResponse
     *
     * @throws NotFoundException
     */
    private function processGetProductByReference(Factory $factory, string $reference): SkuResponse
    {
        $sku = $factory->getSKUService()->getByReference($reference);
        return new SkuResponse($sku);
    }

    /**
     * @param Factory $factory
     * @param Cart $cart
     * @param string $refcode
     * @param int $quantity
     *
     * @return OrderResponse
     * @throws NotFoundException
     * @throws PrestaShopException
     */
    private function processAddProductToOrder(Factory $factory, Cart $cart, string $refcode, int $quantity): OrderResponse
    {
        $product = $factory->getSKUService()->getByReference($refcode);
        $operator = $quantity > 0 ? 'up' : 'down';
        $cart->updateQty(abs($quantity), $product->productId, $product->combinationId, 0, $operator);
        return new OrderResponse($cart);
    }

    /**
     * @param Factory $factory
     * @param Cart $cart
     * @param string $refcode
     *
     * @return OrderResponse
     * @throws NotFoundException
     * @throws PrestaShopException
     */
    private function processDeleteProductFromOrder(Factory $factory, Cart $cart, string $refcode): OrderResponse
    {
        $sku = $factory->getSKUService()->getByReference($refcode);
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
     *
     * @throws AccessDeniedException
     * @throws UnauthorizedException
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
     * @return UserResponse
     *
     * @throws AccessDeniedException
     */
    private function processTokenIntrospection(Factory $factory, Token $token): UserResponse
    {
        $user = $factory->authService()->tokenIntrospection($token);
        return new UserResponse($user);
    }

    /**
     * @param Factory $factory
     * @param Cart $cart
     * @param string $discountType
     * @param float $value
     *
     * @return void
     *
     * @throws PrestaShopException
     * @throws NotFoundException
     * @throws AccessDeniedException
     */
    private function processApplyDiscount(Factory $factory, Cart $cart, string $discountType, float $value): OrderResponse
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

        $employee = $this->getToken()->getEmployee();
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
