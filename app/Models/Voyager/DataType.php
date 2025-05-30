<?php

namespace App\Models\Voyager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Traits\Translatable;

class DataType extends \TCG\Voyager\Models\DataType
{
    use Translatable;

    protected $translatable = ['display_name_singular', 'display_name_plural'];

    protected $table = 'data_types';

    protected $fillable = [
        'name',
        'slug',
        'display_name_singular',
        'display_name_plural',
        'icon',
        'model_name',
        'policy_name',
        'controller',
        'description',
        'generate_permissions',
        'server_side',
        'order_column',
        'order_display_column',
        'order_direction',
        'default_search_key',
        'scope',
        'details',
    ];
    public function updateDataType($requestData, $throw = false)
    {
        try {
            DB::beginTransaction();

            // Prepare data
            foreach (['generate_permissions', 'server_side'] as $field) {
                if (!isset($requestData[$field])) {
                    $requestData[$field] = 0;
                }
            }

            if ($this->fill($requestData)->save()) {
                $fields = $this->fields((strlen($this->model_name) != 0)
                    ? DB::getTablePrefix().app($this->model_name)->getTable()
                    : DB::getTablePrefix().Arr::get($requestData, 'name')
                );

                $requestData = $this->getRelationships($requestData, $fields);

                foreach ($fields as $field) {
                    $field = str_replace(' ', '_', $field);
                    $dataRow = $this->rows()->firstOrNew(['field' => $field]);

                    foreach (['browse', 'read', 'edit', 'add', 'delete'] as $check) {
                        $dataRow->{$check} = isset($requestData["field_{$check}_{$field}"]);
                    }

                    $dataRow->required = boolval($requestData['field_required_'.$field]);
                    $dataRow->field = $requestData['field_'.$field];
                    $dataRow->type = $requestData['field_input_type_'.$field];
                    $dataRow->details = json_decode($requestData['field_details_'.$field]);
                    $dataRow->display_name = $requestData['field_display_name_'.$field];
                    $dataRow->order = intval($requestData['field_order_'.$field]);

                    // Prepare Translations and Transform data
                    $translations = (is_bread_translatable($dataRow) && !empty($requestData['field_display_name_'.$field.'_i18n']))
                        ? $dataRow->prepareTranslationsFromArray($field, $requestData)
                        : [];

                    if (!$dataRow->save()) {
                        throw new \Exception(__('voyager::database.field_safe_failed', ['field' => $field]));
                    }

                    // Save translations if applied
                    $dataRow->saveTranslations($translations);
                }

                // Clean data_rows that don't have an associated field
                // TODO: need a way to identify deleted and renamed fields.
                //   maybe warn the user and let him decide to either rename or delete?
                $this->rows()->whereNotIn('field', $fields)->delete();

                // It seems everything was fine. Let's check if we need to generate permissions
                if ($this->generate_permissions) {
                    Voyager::model('Permission')->generateFor($this->name);
                }

                DB::commit();

                return true;
            }
        } catch (\Exception $e) {
            DB::rollBack();

            if ($throw) {
                throw $e;
            }
        }

        return false;
    }
}
