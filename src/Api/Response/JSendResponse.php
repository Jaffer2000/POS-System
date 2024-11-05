<?php

namespace Thirtybees\Module\POS\Api\Response;


use Thirtybees\Module\POS\DependencyInjection\Factory;

abstract class JSendResponse
{
    const TYPE_SUCCESS = 'success';
    const TYPE_FAIL = 'fail';
    const TYPE_ERROR = 'error';

    /**
     * @return string
     */
    public abstract function getType(): string;

    /**
     * @return int
     */
    public abstract function getResponseCode(): int;

    /**
     * @param Factory $factory
     * @return array
     */
    public function getData(Factory $factory): array
    {
        return [];
    }

    /**
     * @param Factory $factory
     * @return string
     */
    public function getMessage(Factory $factory): string
    {
        return 'Unknown error';
    }

    /**
     * @param Factory $factory
     *
     * @return array
     */
    public final function getResponse(Factory $factory): array
    {
        $type = $this->getType();
        switch ($type) {
            case static::TYPE_SUCCESS:
                return $this->returnSuccess($this->getData($factory));
            case static::TYPE_FAIL:
                return $this->returnFail($this->getData($factory));
            case static::TYPE_ERROR:
                return $this->returnError($this->getMessage($factory));
            default:
                return $this->returnError("Invalid jsend response type: '$type'");
        }
    }

    /**
     * @param string $message
     * @return string[]
     */
    protected function returnError(string $message): array
    {
        return [
            'status' => static::TYPE_ERROR,
            'message' => $message,
        ];
    }

    /**
     * @param array $data
     * @return array
     */
    protected function returnSuccess(array $data): array
    {
        return [
            'status' => static::TYPE_SUCCESS,
            'data' => $data
        ];
    }

    /**
     * @param array $data
     * @return array
     */
    protected function returnFail(array $data): array
    {
        return [
            'status' => static::TYPE_FAIL,
            'data' => $data
        ];
    }
}