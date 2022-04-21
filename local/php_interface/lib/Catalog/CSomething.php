<?
namespace lib\Catalog;
\Bitrix\Main\Loader::includeModule('highloadblock');
use Bitrix\Highloadblock as HL;
use Bitrix\Main\Entity;

class CSomething
{
  static function start()
  {
      $start = new CColor();
      // Цвета (список) торговых предложений
      $colorsSKU = $start->getColorSku();
      // ENUM_ID (список)
      $arEnumIds = array_reduce(
        $colorsSKU,
        function ($result, $color) {
          if ($color['ENUM_ID']) {
            $result[$color['ENUM_ID']] = $color['ENUM_ID'];
          }

          return $result;
        },
        []
      );
      // XML_ID (список)
      $arXML_IDColorSKU = $start->getXML_IDColorSku($arEnumIds, IBLOCK_ID_SKU_CATALOG, "COLOR");
      // XML_ID colors highload
      $arXmlIdColorHighloadBlock = $start->getIdColorFromHighload($arXML_IDColorSKU, HIGHLOAD_BLOCK_COLORS);

      // Сделаем массив с цветами удобным для работы
      $modifiedArXmlIdColorHighloadBlock = $start->getModifiedArIdColorHighloadBlock($arXmlIdColorHighloadBlock);
      // Подготовка массива для обновления
      $arUpdateInfo = $start->getUpdateInfo($colorsSKU, $arXML_IDColorSKU,$modifiedArXmlIdColorHighloadBlock);
      // Обновляем
      if ($arUpdateInfo) {
        foreach ($arUpdateInfo as $elementId => $idColor) {
            \CIBlockElement::SetPropertyValuesEx(
                $elementId,
                $IBLOCK_ID_SKU_CATALOG,
                array('COLOR_REF' => $idColor)
            );
        }
      }



      return "lib\Catalog\CColor::generate();";
  }

  private function getColorSku():array {
    $obSkuColor = [];
    $getColors = \CIBlockElement::GetList(
       array(),
       array(
         'IBLOCK_ID' => IBLOCK_ID_SKU_CATALOG,
         '!PROPERTY_COLOR' => 'false',
         // 'PROPERTY_COLOR_REF' => 'false',
       ),
       false,
       false,
       array('ID', 'PROPERTY_COLOR')
    );
    while($obColors = $getColors->Fetch())
    {
      if ($obColors['PROPERTY_COLOR_VALUE']) {
        $obSkuColor[$obColors['ID']] = [
          'VALUE' => $obColors['PROPERTY_COLOR_VALUE'],
          'ENUM_ID' => $obColors['PROPERTY_COLOR_ENUM_ID'],
        ];
      }
    }

    return $obSkuColor;
  }

  private function getXML_IDColorSku($arEnumIds, $iblockID, $propCode):array {
    $arXML_IDColor = [];
    $property_enums = \CIBlockPropertyEnum::GetList(
      Array("DEF"=>"DESC", "SORT"=>"ASC"),
       Array("IBLOCK_ID"=>$iblockID, "CODE"=>$propCode)
     );
    while($enum_fields = $property_enums->GetNext())
    {
      if ($enum_fields['XML_ID']) {
        $arXML_IDColor[$enum_fields['ID']] = $enum_fields['XML_ID'];
      }
    }

    return $arXML_IDColor;
  }

  private function getIdColorFromHighload($xml_ids, $highloadId):array {
    $idColorFromHighload = [];
    $hlblock = HL\HighloadBlockTable::getById($highloadId)->fetch();
    $entity = HL\HighloadBlockTable::compileEntity($hlblock);
    $entity_data_class = $entity->getDataClass();
    $rsData = $entity_data_class::getList(array(
    	"select" => array("ID","UF_XML_ID_COLOR_1C","UF_XML_ID"),
    	"order" => array("ID" => "ASC"),
    	'filter' => array("UF_XML_ID_COLOR_1C" => $xml_ids)
    ));
    while($arData = $rsData->Fetch())
    {
      if ($arData["UF_XML_ID_COLOR_1C"]) {
        $idColorFromHighload[$arData["UF_XML_ID"]] = $arData["UF_XML_ID_COLOR_1C"];
      }
    }

    return $idColorFromHighload;
  }

  private function getModifiedArIdColorHighloadBlock($arIdColorHighloadBlock):array {
    $modifiedArIdColorHighloadBlock = [];
    foreach ($arIdColorHighloadBlock as $idHighloadRecord => $arXML_ID) {
      foreach ($arXML_ID as $xmlID) {
        $modifiedArIdColorHighloadBlock[$xmlID] = $idHighloadRecord;
      }
    }

    return $modifiedArIdColorHighloadBlock;

  }

  private function getUpdateInfo($colorsSKU, $arXML_IDColorSKU, $arIdColorHighloadBlock):array {
    $arUpdateInfo = [];
    foreach ($colorsSKU as $skuId => $arColorInfo) {
      $xmlIdColorList = $arXML_IDColorSKU[$arColorInfo['ENUM_ID']];
      $idColorHighloadBlock = $arIdColorHighloadBlock[$xmlIdColorList];
      if ($idColorHighloadBlock) {
        $arUpdateInfo[$skuId] = $idColorHighloadBlock;
      }
    }

    return $arUpdateInfo;
  }
}
