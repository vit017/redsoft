<?php

namespace V\Redsoft\FilesCleaner;

class IBlockFilesCleaner
{
    private $docRoot = "";
    private $siteID = "";
    private $iblockFilesDir = "";
    private $regexpSectionUserFields = "/^IBLOCK_[\d]+_SECTION$/";
    private $propertyFileType = "F";

    public function __construct(string $siteID, string $iblockFilesDir)
    {
        $this->siteID = $siteID;
        $this->iblockFilesDir = $iblockFilesDir;
        $this->docRoot = $_SERVER["DOCUMENT_ROOT"];
    }

    public function RunProcess(): array
    {
        $arNotFoundFiles = $this->FindOldFiles();
        return $this->ClearFilesAndDirs($arNotFoundFiles);
    }

    private function FindOldFiles(): array
    {
        $arIBlocksID = $this->GetIBlocksID();
        $arDirFiles = $this->GetDirFiles();
        $arIBlocksFiles = $this->GetIBlocksFiles($arIBlocksID);

        return $this->CompareFiles($arIBlocksFiles, $arDirFiles);
    }

    private function GetIBlocksID(): array
    {
        $arIBlocksID = [];

        $IBList = \CIBlock::GetList(["ID" => "ASC"], ["SITE_ID" => $this->siteID]);
        while ($arRes = $IBList->Fetch()) {
            $arIBlocksID[] = (int)$arRes["ID"];
        }

        return $arIBlocksID;
    }

    private function GetDirFiles(): array
    {
        $arFiles = [];
        $path = $this->docRoot . $this->iblockFilesDir;

        $objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS), \RecursiveIteratorIterator::SELF_FIRST);

        foreach ($objects as $name => $object) {
            if (!is_file($name)) continue;

            $arFiles[] = $name;
        }

        return $arFiles;
    }

    private function GetIBlocksFiles(array $arIBlocksID): array
    {
        $arIBlocksElementsFiles = $this->GetIBlocksElementsFiles($arIBlocksID);
        $arIBlocksSectionsFiles = $this->GetIBlocksSectionsFiles($arIBlocksID);

        return $this->MergeFiles($arIBlocksElementsFiles, $arIBlocksSectionsFiles);
    }

    private function GetIBlocksElementsFiles(array $arIBlocksID): array
    {
        $arFiles = [];

        $arSelect = ["ID", "IBLOCK_ID", "PREVIEW_PICTURE", "DETAIL_PICTURE"];
        $arFilter = ["IBLOCK_ID" => $arIBlocksID];
        $res = \CIBlockElement::GetList(["ID" => "ASC"], $arFilter, false, false, $arSelect);
        while ($ob = $res->GetNextElement()) {
            $arFields = $ob->GetFields();
            $arProps = $ob->GetProperties();

            $arFiles[] = \CFile::GetPath($arFields["PREVIEW_PICTURE"]);
            $arFiles[] = \CFile::GetPath($arFields["DETAIL_PICTURE"]);

            foreach ($arProps as $k => $prop) {
                if ($this->propertyFileType !== $prop["PROPERTY_TYPE"]) continue;

                if ("Y" === $prop["MULTIPLE"]) {
                    foreach ($prop["VALUE"] as $fileID) {
                        $arFiles[] = \CFile::GetPath($fileID);
                    }
                } else {
                    $arFiles[] = \CFile::GetPath($prop["VALUE"]);
                }
            }

            $arFiles = array_values(array_diff($arFiles, [null]));
        }

        return $arFiles;
    }

    private function GetIBlocksSectionsFiles(array $arIBlocksID): array
    {
        $arFiles = [];
        $arSectionFileProps = ["PICTURE", "DETAIL_PICTURE"];
        $arSelect = ["ID", "IBLOCK_ID", "NAME"];

        $arSectionFields = $this->GetSectionFields();

        foreach ($arIBlocksID as $iblockID) {
            $arSectionFileProps = array_merge($arSectionFileProps, $arSectionFields);
            $arSelect = array_merge($arSelect, $arSectionFileProps);
            $arFilter = ["IBLOCK_ID" => $iblockID];
            $res = \CIBlockSection::GetList(["ID" => "ASC"], $arFilter, false, $arSelect, false);
            while ($ob = $res->GetNext()) {
                foreach ($ob as $field => $value) {
                    if (in_array($field, $arSectionFileProps)) {
                        if (is_array($value)) {
                            foreach ($value as $v) {
                                $arFiles[] = \CFile::GetPath($v);
                            }
                        } else {
                            $arFiles[] = \CFile::GetPath($value);
                        }
                    }
                }
            }
        }

        foreach ($arFiles as $i => $path) {
            if (false === strpos($path, $this->iblockFilesDir)) {
                unset($arFiles[$i]);
            }
        }

        return array_values(array_diff($arFiles, [null]));
    }

    private function GetSectionFields(): array
    {
        $arSectionFields = [];

        $rsData = \CUserTypeEntity::GetList(["SORT" => "ASC"], ["USER_TYPE_ID" => "file"]);
        while ($arRes = $rsData->Fetch()) {
            if (preg_match($this->regexpSectionUserFields, $arRes["ENTITY_ID"])) {
                $arSectionFields[$arRes["FIELD_NAME"]] = 1;
            }
        }

        return array_keys($arSectionFields);
    }

    private function MergeFiles(array ...$arrays): array
    {
        return call_user_func_array("array_merge", func_get_args());
    }

    private function CompareFiles(array $arIBlockFiles, array $arDirFiles): array
    {
        array_walk($arIBlockFiles, function (&$path) {
            $path = $this->docRoot . $path;
        });

        return array_diff($arDirFiles, $arIBlockFiles);
    }

    private function ClearFilesAndDirs(array $arFiles): array
    {
        $arRemovedFiles = [];

        foreach ($arFiles as $file) {
            if (!is_file($file)) continue;

            if (unlink($file)) {
                $arRemovedFiles[] = $file;
                $this->RemoveDirRecursive(dirname($file));
            }
        }

        return $arRemovedFiles;
    }

    private function RemoveDirRecursive(string $path)
    {
        if (!is_dir($path)) return;

        $arEls = scandir($path);
        if (empty(array_diff($arEls, ['.', '..']))) {
            rmdir($path);
            $this->RemoveDirRecursive(dirname($path));
        }
    }
}