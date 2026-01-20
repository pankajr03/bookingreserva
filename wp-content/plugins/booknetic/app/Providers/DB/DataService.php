<?php

namespace BookneticApp\Providers\DB;

use BookneticApp\Models\Data;

trait DataService
{
    private static array $data = [];

    private static function _getData($tableName, $id, $key, $default = null)
    {
        if (isset(self::$data[$tableName][$id][$key])) {
            return self::$data[$tableName][$id][$key];
        }

        $data = Data::query()
            ->where('table_name', $tableName)
            ->where('row_id', $id)
            ->where('data_key', $key)
            ->select('data_value')
            ->fetch();

        self::$data[$tableName][$id][$key] = !is_null($data) && isset($data['data_value']) ? $data['data_value'] : $default;

        return self::$data[$tableName][$id][$key];
    }

    private static function _setData($tableName, $id, $key, $value, $updateIfExists = true)
    {
        if ($updateIfExists === true && !is_null(self::_getData($tableName, $id, $key))) {
            $res = Data::query()
                ->where('table_name', $tableName)
                ->where('row_id', $id)
                ->where('data_key', $key)
                ->update(['data_value' => $value]);
        } else {
            $res = Data::query()
                ->insert([
                    'table_name' => $tableName,
                    'row_id' => $id,
                    'data_key' => $key,
                    'data_value' => $value
                ]);
        }

        self::$data[$tableName][$id][$key] = $value;

        return $res;
    }

    private static function _deleteData($tableName, $id = null, $key = null, $value = null)
    {
        unset(self::$data[$tableName][$id][$key]);

        $data = Data::query()->where('table_name', $tableName);

        if (!empty($id)) {
            $data->where('row_id', $id);
        }

        if (!empty($key)) {
            $data->where('data_key', $key);
        }

        if (!empty($value)) {
            $data->where('data_value', $value);
        }

        return $data->delete();
    }
}
