<?php

/*
 * Класс для очистки каталога upload/iblock от неиспользуемых файлов
 *
 * Сравнивает файлы в директории upload/iblock и в таблице БД b_file.
 * Производит удаление из указанной директории отсутствующих файлов в таблице b_file.
 */


namespace V\Redsoft\FilesCleaner;

class IBlockFilesCleaner
{
    private $docRoot = "";
    private $bxFilesDir = "/upload";
    private $iblockFilesDir = "/upload/iblock";


    public function __construct()
    {
        $this->docRoot = $_SERVER["DOCUMENT_ROOT"];
    }

    public function RunProcess(): array
    {
        $arNotFoundFiles = $this->FindOldFiles();
        return $this->ClearFilesAndDirs($arNotFoundFiles);
    }

    private function FindOldFiles(): array
    {
        $arIBlocksFiles = $this->GetIBlocksFiles();
        $arDirFiles = $this->GetDirFiles();

        return $this->CompareFiles($arIBlocksFiles, $arDirFiles);
    }

    private function GetIBlocksFiles(): array {
        global $DB;
        $arFiles = [];

        $result = $DB->Query("SELECT FILE_NAME, SUBDIR FROM b_file WHERE MODULE_ID = 'iblock'");
        while ($row = $result->Fetch()) {
            $arFiles[] = $this->docRoot.$this->bxFilesDir."/".$row["SUBDIR"]."/".$row["FILE_NAME"];
        }

        return $arFiles;
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

    private function CompareFiles(array $arIBlockFiles, array $arDirFiles): array
    {
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
        if (empty(array_diff($arEls, [".", ".."]))) {
            rmdir($path);
            $this->RemoveDirRecursive(dirname($path));
        }
    }
}