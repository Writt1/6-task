<?php

namespace MyComponents\NewsListV2;

use CBitrixComponent;
use CIBlock;
use Bitrix\Iblock;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

class NewsListV2 extends CBitrixComponent
{
    public function onPrepareComponentParams($arParams): array
    {
        $arParams["IBLOCK_TYPE"] = trim($arParams["IBLOCK_TYPE"] ?? '');
        if (empty($arParams["IBLOCK_TYPE"]))
        {
            $arParams["IBLOCK_TYPE"] = "news";
        }
        $arParams["IBLOCK_ID"] = trim($arParams["IBLOCK_ID"] ?? '');

        return $arParams;
    }

    public function executeComponent(): void
    {
        if ($this->startResultCache())
        {
            $this->initResult();

            if (empty($this->arResult))
            {
                $this->abortResultCache();
                ShowError('Элементы не найдены');

                return;
            }

            $this->includeComponentTemplate();
        }
    }

    private function initResult(): void
    {
        $this->arResult = ['ITEMS' => []];

        $iblockFilter = [
            'ACTIVE' => 'Y',
        ];

        if (!empty($this->arParams['IBLOCK_ID'])) {
            $iblockFilter['ID'] = (int)$this->arParams['IBLOCK_ID'];
        } else {
            $iblockFilter['TYPE'] = $this->arParams['IBLOCK_TYPE'];
        }

        $rsIBlocks = CIBlock::GetList(['SORT' => 'ASC'], $iblockFilter);

        while ($arIBlock = $rsIBlocks->Fetch()) {
            $iblockId = (int)$arIBlock['ID'];

            $elements = \CIBlockElement::GetList(
                ['SORT' => 'ASC'],
                ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y'],
                false,
                ['nTopCount' => $this->arParams['NEWS_COUNT']],
                ['ID', 'NAME', 'PREVIEW_TEXT', 'DETAIL_PAGE_URL', 'PREVIEW_PICTURE', 'DETAIL_PICTURE']
            );

            while ($el = $elements->GetNext()) {
                $ipropValues = new Iblock\InheritedProperty\ElementValues($iblockId, $el['ID']);
                $el['IPROPERTY_VALUES'] = $ipropValues->getValues();

                Iblock\Component\Tools::getFieldImageData(
                    $el,
                    ['PREVIEW_PICTURE', 'DETAIL_PICTURE'],
                    Iblock\Component\Tools::IPROPERTY_ENTITY_ELEMENT,
                    'IPROPERTY_VALUES'
                );

                $this->arResult['ITEMS'][$iblockId][] = $el;
            }
        }
    }
}
?>