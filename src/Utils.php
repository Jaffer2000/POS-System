<?php

namespace Thirtybees\Module\POS;

use Configuration;
use Context;
use Db;
use DbQuery;
use ImageType;
use Module;
use PrestaShopException;
use Product;
use Shop;

class Utils
{

    /**
     * Returns product image types that are protected using watermark functionality
     *
     * @return array
     * @throws PrestaShopException
     */
    public static function getWatermarkImageTypes()
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
     * @param int $productId
     * @param int $combinationId
     * @param string $rewrite
     * @param string $imageType
     * @return string
     * @throws PrestaShopException
     */
    public static function getProductImageUrl(int $productId, int $combinationId, string $rewrite, string $imageType = 'home')
    {
        $idLang = (int)Context::getContext()->language->id;

        $link = Context::getContext()->link;
        $imageInfo = null;
        if ($combinationId) {
            $imageInfo = Product::getCombinationImageById($combinationId, $idLang);
        }
        if (! $imageInfo) {
            $imageInfo = Product::getCover($productId);
        }

        $imageId = 0;
        if ($imageInfo && isset($imageInfo['id_image'])) {
            $imageId = (int)$imageInfo['id_image'];
        }

        if ($imageId) {
            if (! $rewrite) {
                $rewrite = Db::getInstance()->getValue((new DbQuery())
                    ->select('link_rewrite')
                    ->from('product_lang', 'pl')
                    ->where('id_product = ' . (int)$productId)
                    ->where('id_lang = ' . $idLang . Shop::addSqlRestrictionOnLang('pl'))
                );
                if (! $rewrite) {
                    $rewrite = "$imageId";
                }
            }
            return $link->getImageLink($rewrite, $imageId, static::getFormattedImageType($imageType));
        } else {
            return '';
        }
    }



    /**
     * @param string $imageType
     * @return string
     * @throws PrestaShopException
     */
    public static function getFormattedImageType($imageType)
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

}