<?php
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

namespace Thirtybees\Module\POS\Sku\Service;

use Configuration;
use Context;
use Db;
use DbQuery;
use ImageType;
use Module;
use PrestaShopDatabaseException;
use PrestaShopException;
use Product;
use Product as CoreProduct;
use Shop;
use Thirtybees\Module\POS\Exception\NotFoundException;
use Thirtybees\Module\POS\Sku\Model\Sku;
use Tools;

class SkuServiceImpl implements SkuService
{

    /**
     * @param string $reference
     *
     * @return Sku
     *
     * @throws NotFoundException *@throws PrestaShopException
     * @throws PrestaShopException
     */
    public function getByReference(string $reference): Sku
    {
        $res = $this->findByReference($reference);
        if ($res) {
            return $res;
        }
        throw new NotFoundException("Product with reference code '".$reference."' not found");
    }

    /**
     * @param string $reference
     *
     * @return Sku|null
     * @throws PrestaShopException
     */
    public function findByReference(string $reference):?Sku
    {
        $resp = $this->findProductByReference($reference);
        if ($resp) {
            return $resp;
        }
        $resp = $this->findCombinationByReference($reference);
        if ($resp) {
            return $resp;
        }
        return null;
    }

    /**
     * @param int $productId
     * @param int $combinationId
     *
     * @return Sku
     * @throws PrestaShopException
     */
    public function findById(int $productId, int $combinationId):?Sku
    {
        $productId = (int)$productId;
        $combinationId = (int)$combinationId;
        if ($combinationId) {
            return $this->findCombinationById($productId, $combinationId);
        } else {
            return $this->findProductById($productId);
        }
    }

    /**
     * @param int $productId
     * @param int $combinationId
     *
     * @return Sku
     * @throws NotFoundException
     * @throws PrestaShopException
     */
    public function getById(int $productId, int $combinationId): Sku
    {
        $product = $this->findById($productId, $combinationId);
        if (! $product) {
            throw new NotFoundException("Product with id $productId/$combinationId not found");
        }
        return $product;
    }

    /**
     * @param int $productId
     *
     * @return Sku
     * @throws PrestaShopException
     */
    protected function findProductById(int $productId)
    {
        $languageId = (int)Context::getContext()->language->id;
        $conn = Db::getInstance();
        $sql = (new DbQuery())
            ->select('p.id_product')
            ->select('p.reference')
            ->select('pl.name')
            ->select('pl.link_rewrite')
            ->from('product', 'p')
            ->innerJoin('product_lang', 'pl', '(pl.id_product = p.id_product AND pl.id_lang = '.$languageId . Shop::addSqlRestrictionOnLang('pl').')')
            ->where('p.id_product = ' . $productId);
        $row = $conn->getRow($sql);
        if ($row === false) {
            return null;
        }
        $combinationId = 0;
        return new Sku(
            $productId,
            $row['name'] ?? '',
            $combinationId,
            '',
            $row['reference'],
            $this->getProductImageUrl($productId, $combinationId, 'home', $row['link_rewrite']),
        );
    }

    /**
     * @param int $productId
     * @param int $combinationId
     *
     * @return Sku
     * @throws PrestaShopException
     */
    protected function findCombinationById(int $productId, int $combinationId): ?Sku
    {
        $languageId = (int)Context::getContext()->language->id;
        $conn = Db::getInstance();
        $sql = (new DbQuery())
            ->select('pa.id_product_attribute')
            ->select('p.id_product')
            ->select('pa.reference')
            ->select('pl.name')
            ->select('pl.link_rewrite')
            ->select(static::getCombinationNameSubquery('combination_name', 'pa', $languageId))
            ->from('product_attribute', 'pa')
            ->innerJoin('product', 'p', '(pa.id_product = p.id_product)')
            ->innerJoin('product_lang', 'pl', '(pl.id_product = p.id_product AND pl.id_lang = ' . $languageId . Shop::addSqlRestrictionOnLang('pl') . ')')
            ->where('pa.id_product_attribute = ' . $combinationId)
            ->where('p.id_product = ' . $productId);
        $row = $conn->getRow($sql);
        if ($row === false) {
            return null;
        }
        return new Sku(
            $productId,
            $row['name'] ?? '',
            $combinationId,
            $row['combination_name'] ?? '',
            $row['reference'],
            $this->getProductImageUrl($productId, $combinationId, 'home', $row['link_rewrite']),
        );
    }

    /**
     * @param string $reference
     *
     * @return Sku | false
     * @throws PrestaShopException
     */
    protected function findProductByReference(string $reference)
    {
        $languageId = (int)Context::getContext()->language->id;
        $conn = Db::getInstance();
        $sql = (new DbQuery())
            ->select('p.id_product')
            ->select('p.reference')
            ->select('pl.name')
            ->select('pl.link_rewrite')
            ->from('product', 'p')
            ->innerJoin('product_lang', 'pl', '(pl.id_product = p.id_product AND pl.id_lang = '.$languageId . Shop::addSqlRestrictionOnLang('pl').')')
            ->where('p.reference = "'.pSQL($reference).'"');
        $row = $conn->getRow($sql);
        if ($row === false) {
            return false;
        }
        $productId = (int)$row['id_product'];
        $combinationId = 0;
        return new Sku(
            $productId,
            $row['name'] ?? '',
            $combinationId,
            '',
            $row['reference'],
            $this->getProductImageUrl($productId, $combinationId, 'home', $row['link_rewrite']),
        );
    }

    /**
     * @param string $reference
     *
     * @return Sku | false
     * @throws PrestaShopException
     */
    protected function findCombinationByReference(string $reference)
    {
        $languageId = (int)Context::getContext()->language->id;
        $conn = Db::getInstance();
        $sql = (new DbQuery())
            ->select('pa.id_product_attribute')
            ->select('p.id_product')
            ->select('pa.reference')
            ->select('pl.name')
            ->select('pl.link_rewrite')
            ->select(static::getCombinationNameSubquery('combination_name', 'pa', $languageId))
            ->from('product_attribute', 'pa')
            ->innerJoin('product', 'p', '(pa.id_product = p.id_product)')
            ->innerJoin('product_lang', 'pl', '(pl.id_product = p.id_product AND pl.id_lang = '.$languageId . Shop::addSqlRestrictionOnLang('pl').')')
            ->where('pa.reference = "'.pSQL($reference).'"');
        $row = $conn->getRow($sql);
        if ($row === false) {
            return false;
        }
        $productId = (int)$row['id_product'];
        $combinationId = (int)$row['id_product_attribute'];
        return new Sku(
            $productId,
            $row['name'] ?? '',
            $combinationId,
            $row['combination_name'] ?? '',
            $row['reference'],
            $this->getProductImageUrl($productId, $combinationId, 'home', $row['link_rewrite']),
        );
    }

    /**
     * @param string $alias
     * @param string $productAttributeAlias
     * @param int $idLang
     * @param string $attributeValueSeparator
     * @param string $attributeSeparator
     *
     * @return string
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    private static function getCombinationNameSubquery(string $alias, string $productAttributeAlias, int $idLang, string $attributeValueSeparator = ' - ', string $attributeSeparator = ', ')
    {
        return 'COALESCE((
                SELECT GROUP_CONCAT(agl.`name`, \''.pSQL($attributeValueSeparator).'\',al.`name` ORDER BY agl.`id_attribute_group` SEPARATOR \''.pSQL($attributeSeparator).'\')
                 FROM `'._DB_PREFIX_.'product_attribute_combination` pac
                 LEFT JOIN `'._DB_PREFIX_.'attribute` a ON a.`id_attribute` = pac.`id_attribute`
                 LEFT JOIN `'._DB_PREFIX_.'attribute_group` ag ON ag.`id_attribute_group` = a.`id_attribute_group`
                 LEFT JOIN `'._DB_PREFIX_.'attribute_lang` al ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = '.(int) $idLang.')
                 LEFT JOIN `'._DB_PREFIX_.'attribute_group_lang` agl ON (ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = '.(int) $idLang.')
                 WHERE pac.id_product_attribute  = pa.id_product_attribute
                 GROUP BY pac.id_product_attribute
           ), \'\') AS `'.$alias.'`';
    }

    /**
     * @param int $productId
     * @param int $combinationId
     * @param string $imageType
     * @param string|null $rewrite
     *
     * @return string
     * @throws PrestaShopException
     */
    public function getProductImageUrl(int $productId, int $combinationId, string $imageType, string $rewrite = null)
    {
        $idLang = (int)Context::getContext()->language->id;

        $link = Context::getContext()->link;
        $imageInfo = null;
        if ($combinationId) {
            $imageInfo = CoreProduct::getCombinationImageById($combinationId, $idLang);
        }
        if (! $imageInfo) {
            $imageInfo = CoreProduct::getCover($productId);
        }

        $imageId = 0;
        if ($imageInfo && isset($imageInfo['id_image'])) {
            $imageId = (int)$imageInfo['id_image'];
        }

        if (is_null($rewrite) && $imageId) {
            $rewrite = Db::getInstance()->getValue((new DbQuery())
                ->select('link_rewrite')
                ->from('product_lang')
                ->where('id_product = ' . (int)$productId)
                ->where('id_lang = ' . $idLang . Shop::addSqlRestrictionOnLang('pl'))
            );
        }

        return $link->getImageLink($rewrite ?? '', $imageId, $this->getFormattedImageType($imageType));
    }

    /**
     * @param string $imageType
     * @return string
     * @throws PrestaShopException
     */
    private static function getFormattedImageType($imageType)
    {
        $formattedType = ImageType::getFormatedName($imageType) ?? '';
        if ($formattedType
            && Module::isInstalled('watermark')
            && Module::isEnabled('watermark')
        ) {
            $watermarkTypes = static::getWatermarkImageTypes();
            if (isset($watermarkTypes[$formattedType])) {
                return $watermarkTypes[$formattedType];
            }
        }
        return $formattedType;
    }

    /**
     * Returns product image types that are protected using watermark functionality
     *
     * @return array
     * @throws PrestaShopException
     */
    private static function getWatermarkImageTypes()
    {
        static $watermarkTypes = null;
        if (is_null($watermarkTypes)) {
            $watermarkTypes = [];
            $selectedTypes = Configuration::get('WATERMARK_TYPES');
            if ($selectedTypes) {
                $selectedTypes = array_map('intval', explode(',', $selectedTypes));
                if ($selectedTypes) {
                    $hash = Configuration::get('WATERMARK_HASH');
                    foreach (ImageType::getImagesTypes('products') as $imageType) {
                        if (in_array((int)$imageType['id_image_type'], $selectedTypes)) {
                            $imageTypeName = $imageType['name'];
                            $watermarkTypes[$imageTypeName] = $imageTypeName . '-' . $hash;
                        }
                    }
                }
            }
        }
        return $watermarkTypes;
    }

    /**
     * @return Sku[]
     *
     * @throws PrestaShopException
     */
    public function findAll(): array
    {
        $languageId = (int)Context::getContext()->language->id;
        $conn = Db::getInstance();
        $sql = (new DbQuery())
            ->select('p.id_product')
            ->select('p.reference')
            ->select('pl.name')
            ->select('pl.link_rewrite')
            ->from('product', 'p')
            ->innerJoin('product_lang', 'pl', '(pl.id_product = p.id_product AND pl.id_lang = '.$languageId . Shop::addSqlRestrictionOnLang('pl').')');
        $res = $conn->getArray($sql);

        return array_map(function($row) {
            $productId = (int)$row['id_product'];
            $combinationId = 0;
            return new Sku(
                $productId,
                $row['name'] ?? '',
                $combinationId,
                '',
                $row['reference'],
                $this->getProductImageUrl($productId, $combinationId, 'home', $row['link_rewrite']),
            );
        }, $res);
    }

    /**
     * @param int $productId
     * @param int $combinationId
     *
     * @return float
     *
     * @throws PrestaShopException
     */
    private function getProductPrice(int $productId, int $combinationId): float
    {
        return Tools::roundPrice(Product::getPriceStatic($productId, $combinationId));
    }

}