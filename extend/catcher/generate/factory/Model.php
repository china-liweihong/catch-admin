<?php
namespace catcher\generate\factory;

use catcher\exceptions\FailedException;
use catcher\generate\template\Model as Template;
use think\facade\Db;
use think\helper\Str;

class Model extends Factory
{
    public function done($params)
    {
        $content = $this->getContent($params);

        file_put_contents($this->getGeneratePath($params['model']), $content);

        if (!file_exists($this->getGeneratePath($params['model']))) {
            throw new FailedException('create model failed');
        }

        return true;
    }

    /**
     * get contents
     *
     * @time 2020年04月29日
     * @param $params
     * @return string|string[]
     */
    public function getContent(&$params)
    {
        // TODO: Implement done() method.
        $template = new Template();

        $extra = $params['extra'];

        $table = $params['table'];

        [$modelName, $namespace] = $this->parseFilename($params['model']);

        // 如果填写了表名并且没有填写模型名称 使用表名作为模型名称
        if (!$modelName && $table) {
            $modelName = ucfirst(Str::camel($table));
            $params['model'] = $params['model'] . $modelName;
        }

        if (!$modelName) {
            throw new FailedException('model name not set');
        }

        $content = $template->useTrait($extra['soft_delete']) .
            $template->name($table) .
            $template->field($this->parseField($table));

        $class = $template->header() .
            $template->nameSpace($namespace) .
            $template->uses($extra['soft_delete']) .
            $template->createModel($modelName, $table);

        return str_replace('{CONTENT}', $content, $class);
    }

    /**
     * parse field
     *
     * @time 2020年04月28日
     * @param $table
     * @return string
     */
    protected function parseField($table)
    {
        if (!$this->hasTableExists($table)) {
            return false;
        }

        $columns = Db::query('show full columns from ' .
            config('database.connections.mysql.prefix') . $table);

        $new = [];
        foreach ($columns as $field) {
            $new[$field['Field']] = $field['Comment'];
        }

        $fields = [];
        foreach ($new as $field => $comment) {
            $fields[] = sprintf("'%s', // %s", $field, $comment);
        }

        return implode("\r\n\t\t", $fields);
    }
}