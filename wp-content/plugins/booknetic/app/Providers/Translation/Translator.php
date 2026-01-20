<?php

namespace BookneticApp\Providers\Translation;

use BookneticApp\Models\Translation;
use BookneticApp\Providers\DB\Collection;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Request\Post;

trait Translator
{
    /**
     * Check if the model which uses this trait is translatable
     * @return boolean
     */
    protected static function isTranslatable(): bool
    {
        if (isset(self::$translations) && self::$translations === false) {
            return false;
        }

        return !empty(self::getTranslatableAttributes());
    }

    /**
     * @returns array
     */
    protected static function getTranslatableAttributes(): array
    {
        return property_exists(static::class, "translations") ? self::$translations : [];
    }

    protected static function isTranslatableAttribute($attribute): bool
    {
        if (empty($attribute)) {
            return false;
        }

        return in_array($attribute, self::getTranslatableAttributes());
    }

    public static function handleTranslation($rowId, $data = ''): void
    {
        if (!$data) {
            $data = Post::string('translations');
        }

        $translations = json_decode($data, true);
        if (empty($translations) || !is_array($translations) || !self::isTranslatable()) {
            return;
        }

        foreach ($translations as $column => $translation) {
            self::handleSingleFieldTranslations($column, $rowId, $translation);
        }
    }

    public static function handleSingleFieldTranslations($col, $rowId, $languages): void
    {
        if (!is_array($languages) || !self::isTranslatableAttribute($col)) {
            return;
        }

        foreach ($languages as $language) {
            $locale = $language['locale'] ?? '';
            $value = $language['value'] ?? '';

            if (empty($locale)) {
                return;
            }

            if (!empty($language['id'])) {
                Translation::query()
                    ->where('id', $language['id'])
                    ->update([
                        'locale' => $locale,
                        'value' => $value
                    ]);
                continue;
            }

            Translation::query()
                ->insert([
                    'row_id' => $rowId,
                    'table_name' => self::getTableName(),
                    'column_name' => $col,
                    'locale' => $locale,
                    'value' => $value
                ]);
        }
    }

    public static function translateData($data)
    {
        if (!isset($data['id'])) {
            return $data;
        }

        $translations = self::getTranslatedAttributes($data['id'], self::getTranslatableAttributes());

        foreach ($translations as $translation) {
            $column = $translation['column_name'];
            $value = $translation['value'];

            $data->$column = $value;
        }

        return $data;
    }

    /**
     * @param int $id
     * @param array $columns
     * @return Translation[]|Collection[]
     */
    public static function getTranslatedAttributes(int $id, array $columns): array
    {
        return Translation::query()
            ->where([
                'row_id' => $id,
                'table_name' => self::getTableName(),
                'locale' => Helper::getLocale()
            ])
            ->where('column_name', 'in', $columns)
            ->select([
                'column_name',
                'value'
            ])
            ->fetchAll();
    }

    public static function getTranslatedAttribute($id, $column, $default)
    {
        $translation = Translation::query()
            ->where([
                'row_id' => $id,
                'column_name' => $column,
                'table_name' => self::getTableName(),
                'locale' => Helper::getLocale()
            ])->fetch();

        if ($translation) {
            return $translation['value'];
        }

        return $default;
    }
}
