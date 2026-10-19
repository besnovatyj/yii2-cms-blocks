<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Blocks\files;

use Besnovatyj\Helpers\FilesystemHelper;
use Yii;

/**
 * Папка файлов блока: `@static/origin/Blocks/{id}`.
 *
 * По соглашению проекта у каждой сущности своя папка, и файловый менеджер в её форме заперт в этой
 * папке. Здесь собрано всё, что о ней надо знать: путь на диске, виртуальный путь для проводника,
 * публичный URL и перевод выбранного в проводнике пути в имя файла внутри папки.
 *
 * В `data` блока хранится путь файла ОТНОСИТЕЛЬНО папки блока (`poster.jpg`, `cards/1.jpg`). Так данные
 * не зависят от адреса домена статики, а файл вне папки блока сослаться в данных просто не может.
 */
final class BlockFiles
{
    /** Каталог папок блоков (алиас). */
    public const string ORIGIN = '@static/origin/Blocks';

    /** Каталог производных файлов блоков (миниатюры), см. {@see \Besnovatyj\Blocks\Module::directories()}. */
    public const string CACHE = '@static/cache/Blocks';

    /** Виртуальный путь каталога блоков в проводнике: точка монтирования `static` + путь в ней. */
    private const string VIRTUAL_ROOT = '/static/origin/Blocks';

    /** Расширения, которые принимает поле `image`. */
    public const array IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg'];

    /**
     * Абсолютный путь к папке блока.
     */
    public function dir(int $blockId): string
    {
        return Yii::getAlias(self::ORIGIN) . '/' . $blockId;
    }

    /**
     * Виртуальный путь папки блока для проводника и редактора (`fmDefaultPath`, scope).
     */
    public function virtualDir(int $blockId): string
    {
        return self::VIRTUAL_ROOT . '/' . $blockId;
    }

    /**
     * Виртуальный путь файла блока (то, что показывает поле выбора в форме).
     */
    public function virtualPath(int $blockId, string $file): string
    {
        return $file === '' ? '' : $this->virtualDir($blockId) . '/' . $file;
    }

    /**
     * Публичный URL файла блока; пустая строка — файла нет.
     */
    public function url(int $blockId, string $file): string
    {
        if ($file === '') {
            return '';
        }

        $segments = array_map('rawurlencode', explode('/', $file));

        return rtrim((string)Yii::getAlias('@staticHostName'), '/')
            . '/origin/Blocks/' . $blockId . '/' . implode('/', $segments);
    }

    /**
     * Имя файла внутри папки блока из того, что пришло из формы.
     *
     * Принимает виртуальный путь проводника (`/static/origin/Blocks/5/a.jpg`) или уже относительный
     * (`a.jpg`). Всё, что указывает за пределы папки блока, отбрасывается: null.
     */
    public function relative(int $blockId, string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $prefix = $this->virtualDir($blockId) . '/';
        if (str_starts_with($value, $prefix)) {
            $value = substr($value, strlen($prefix));
        } elseif (str_starts_with($value, '/')) {
            return null;
        }

        if (str_contains($value, '\\') || str_contains($value, "\0")) {
            return null;
        }
        foreach (explode('/', $value) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return null;
            }
        }

        return $value;
    }

    /**
     * Лежит ли файл в папке блока.
     */
    public function exists(int $blockId, string $file): bool
    {
        return $file !== '' && is_file($this->dir($blockId) . '/' . $file);
    }

    /**
     * Создать папку блока, если её нет.
     */
    public function ensure(int $blockId): void
    {
        $dir = $this->dir($blockId);
        if (!is_dir($dir)) {
            FilesystemHelper::createDirectoryRecursively($dir);
        }
    }

    /**
     * Удалить папку блока вместе с содержимым. Нет папки — не ошибка.
     */
    public function remove(int $blockId): void
    {
        $dir = $this->dir($blockId);
        if (is_dir($dir)) {
            FilesystemHelper::deleteDirContents($dir, true);
        }
    }
}
