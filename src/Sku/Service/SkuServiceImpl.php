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

use Context;
use Db;
use DbQuery;
use PrestaShopDatabaseException;
use PrestaShopException;
use Shop;
use Thirtybees\Module\POS\Exception\NotFoundException;
use Thirtybees\Module\POS\Sku\Model\Sku;

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
            ->select('p.ean13')
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
            (string)$row['name'],
            $combinationId,
            '',
            (string)$row['reference'],
            (string)$row['ean13'],
            (string)$row['link_rewrite']
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
            ->select('pa.ean13')
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
            (string)$row['name'],
            $combinationId,
            (string)$row['combination_name'],
            (string)$row['reference'],
            (string)$row['ean13'],
            (string)$row['link_rewrite']
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
            ->select('p.ean13')
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
            (string)$row['name'],
            $combinationId,
            '',
            (string)$row['reference'],
            (string)$row['ean13'],
            (string)$row['link_rewrite']
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
            ->select('pa.ean13')
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
            (string)$row['name'],
            $combinationId,
            (string)$row['combination_name'],
            (string)$row['reference'],
            (string)$row['ean13'],
            (string)$row['link_rewrite']
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
     * @return Sku[]
     *
     * @throws PrestaShopException
     */
    public function find(string $type, string $search): array
    {
        $languageId = (int)Context::getContext()->language->id;
        $conn = Db::getInstance();

        $sql = (new DbQuery())
            ->select('p.id_product')
            ->select('coalesce(NULLIF(pa.reference, ""), p.reference) as reference')
            ->select('coalesce(NULLIF(pa.ean13, ""), p.ean13) as ean13')
            ->select('pl.name')
            ->select('pl.link_rewrite')
            ->select('pas.id_product_attribute')
            ->select(static::getCombinationNameSubquery('combination_name', 'pa', $languageId))
            ->from('product', 'p')
            ->innerJoin('product_lang', 'pl', '(pl.id_product = p.id_product AND pl.id_lang = '.$languageId . Shop::addSqlRestrictionOnLang('pl').')')
            ->leftJoin('product_attribute', 'pa', '(pa.id_product = p.id_product)')
            ->leftJoin('product_attribute_shop', 'pas', '(pas.id_product = p.id_product AND pas.id_product_attribute = pa.id_product_attribute'.Shop::addSqlRestriction(false, 'pas').')');

        if ($search) {
            $where = [];
            if ($type === static::SEARCH_ALL || $type === static::SEARCH_BARCODE) {
                $where[] = 'i.ean13 LIKE \'%' . pSQL($search) . '%\'';
            }
            if ($type === static::SEARCH_ALL || $type === static::SEARCH_NAME) {
                $where[] = 'i.name LIKE \'%' . pSQL($search) . '%\'';
            }
            if ($type === static::SEARCH_ALL || $type === static::SEARCH_REFERENCE) {
                $where[] = 'i.reference LIKE \'%' . pSQL($search) . '%\'';
            }

            $sql = "SELECT i.* FROM ($sql) AS i WHERE " . implode(' OR ', $where);
        }
        $res = $conn->getArray($sql);

        return array_map(function($row) {
            $productId = (int)$row['id_product'];
            $combinationId = (int)$row['id_product_attribute'];
            return new Sku(
                $productId,
                (string)$row['name'],
                $combinationId,
                (string)$row['combination_name'],
                (string)$row['reference'],
                (string)$row['ean13'],
                (string)$row['link_rewrite']
            );
        }, $res);
    }


}