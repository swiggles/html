<?php namespace Orchestra\Html\Table;

use InvalidArgumentException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Fluent;
use Orchestra\Support\Traits\QueryFilterTrait;

class Grid extends \Orchestra\Html\Abstractable\Grid
{
    use QueryFilterTrait;

    /**
     * All the columns.
     *
     * @var array
     */
    protected $columns = array();

    /**
     * Set the no record message.
     *
     * @var string
     */
    public $empty = null;

    /**
     * Eloquent model used for table.
     *
     * @var mixed
     */
    protected $model = null;

    /**
     * List of rows in array, is used when model is null.
     *
     * @var array
     */
    protected $rows = null;

    /**
     * Enable to attach pagination during rendering.
     *
     * @var boolean
     */
    protected $paginate = false;

    /**
     * The number of models/entities to return for pagination.
     *
     * @var int|null
     */
    protected $perPage;

    /**
     * Selected view path for table layout.
     *
     * @var array
     */
    protected $view = null;

    /**
     * {@inheritdoc}
     */
    protected $definition = array(
        'name'    => 'columns',
        '__call'  => array('columns', 'view'),
        '__get'   => array('attributes', 'columns', 'model', 'paginate', 'view', 'rows'),
        '__set'   => array('attributes'),
        '__isset' => array('attributes', 'columns', 'model', 'paginate', 'view'),
    );

    /**
     * {@inheritdoc}
     */
    protected function initiate()
    {
        $config = $this->app['config']->get('orchestra/html::table', array());

        foreach ($config as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }

        $this->rows = new Fluent(array(
            'data'       => array(),
            'attributes' => function () {
                return array();
            },
        ));
    }

    /**
     * Attach Eloquent as row and allow pagination (if required).
     *
     * <code>
     *      // add model without pagination
     *      $table->with(User::all(), false);
     *
     *      // add model with pagination
     *      $table->with(User::paginate(30), true);
     * </code>
     *
     * @param  mixed    $model
     * @param  bool     $paginate
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function with($model, $paginate = true)
    {
        $this->model    = $model;
        $this->paginate = $paginate;

        return $this;
    }

    /**
     * Set table layout (view).
     *
     * <code>
     *      // use default horizontal layout
     *      $table->layout('horizontal');
     *
     *      // use default vertical layout
     *      $table->layout('vertical');
     *
     *      // define table using custom view
     *      $table->layout('path.to.view');
     * </code>
     *
     * @param  string   $name
     * @return $this
     */
    public function layout($name)
    {
        if (in_array($name, array('horizontal', 'vertical'))) {
            $this->view = "orchestra/html::table.{$name}";
        } else {
            $this->view = $name;
        }

        return $this;
    }

    /**
     * Get whether current setup is paginated.
     *
     * @return bool
     */
    public function paginated()
    {
        return $this->paginate;
    }

    /**
     * Attach rows data instead of assigning a model.
     *
     * <code>
     *      // assign a data
     *      $table->rows(DB::table('users')->get());
     * </code>
     *
     * @param  array    $rows
     * @return array
     * @throws \InvalidArgumentException
     */
    public function rows(array $rows = null)
    {
        if (is_null($rows)) {
            return $this->query();
        }

        return $this->setRowsData($rows);
    }

    /**
     * Append a new column to the table.
     *
     * <code>
     *      // add a new column using just field name
     *      $table->column('username');
     *
     *      // add a new column using a label (header title) and field name
     *      $table->column('User Name', 'username');
     *
     *      // add a new column by using a field name and closure
     *      $table->column('fullname', function ($column)
     *      {
     *          $column->label = 'User Name';
     *          $column->value = function ($row) {
     *              return $row->first_name.' '.$row->last_name;
     *          };
     *
     *          $column->attributes(function ($row) {
     *              return array('data-id' => $row->id);
     *          });
     *      });
     * </code>
     *
     * @param  mixed        $name
     * @param  mixed|null   $callback
     * @return \Illuminate\Support\Fluent
     */
    public function column($name, $callback = null)
    {
        list($name, $column) = $this->buildColumn($name, $callback);

        $this->columns[]      = $column;
        $this->keyMap[$name] = count($this->columns) - 1;

        return $column;
    }

    /**
     * Setup pagination.
     *
     * @param  int|null $perPage
     * @return $this
     */
    public function paginate($perPage)
    {
        if (filter_var($perPage, FILTER_VALIDATE_BOOLEAN)) {
            $this->perPage = null;
            $this->paginate = $perPage;
        } elseif (filter_var($perPage, FILTER_VALIDATE_INT, array('options' => array('min_range' => 1)))) {
            $this->perPage = $perPage;
            $this->paginate = true;
        } else {
            $this->perPage = null;
            $this->paginate = false;
        }

        return $this;
    }

    /**
     * Execute searchable filter on model instance.
     *
     * @param  array    $attributes
     * @param  string   $key
     * @return void
     */
    public function searchable(array $attributes, $key = 'q')
    {
        $model = $this->resolveQueryBuilderFromModel();

        $value = $this->app['request']->input($key);

        $this->set('search', array(
            'attributes' => $attributes,
            'key'        => $key,
            'value'      => $value,
        ));

        $this->model = $this->setupWildcardQueryFilter($model, $value, $attributes);
    }

    /**
     * Execute sortable query filter on model instance.
     *
     * @param  string   $orderByKey
     * @param  string   $directionKey
     * @return void
     */
    public function sortable($orderByKey = 'order_by', $directionKey = 'direction')
    {
        $model = $this->resolveQueryBuilderFromModel();

        $orderByValue   = $this->app['request']->input($orderByKey);
        $directionValue = $this->app['request']->input($directionKey);

        $this->set('filter.order_by', array(
            'key'   => $orderByKey,
            'value' => $orderByValue,
        ));

        $this->set('filter.direction', array(
            'key'   => $directionKey,
            'value' => $directionValue,
        ));

        $this->model = $this->setupBasicQueryFilter($model, array(
            'order_by'  => $orderByValue,
            'direction' => $directionValue,
        ));
    }

    /**
     * Build control.
     *
     * @param  mixed    $name
     * @param  mixed    $callback
     * @return array
     */
    protected function buildColumn($name, $callback = null)
    {
        list($label, $name, $callback) = $this->buildFluentAttributes($name, $callback);

        if (! empty($name)) {
            $value = function ($row) use ($name) {
                return data_get($row, $name);
            };
        } else {
            $value = '';
        }

        $column = new Column(array(
            'id'         => $name,
            'label'      => $label,
            'value'      => $value,
            'headers'    => array(),
            'attributes' => function ($row) {
                return array();
            },
        ));

        if (is_callable($callback)) {
            call_user_func($callback, $column);
        }

        return array($name, $column);
    }

    /**
     * Convert the model to Paginator when available or convert it
     * to a collection.
     *
     * @param  mixed    $model
     * @return \Illuminate\Contracts\Support\Arrayable|array
     */
    protected function buildModel($model)
    {
        if ($this->paginate === true && method_exists($model, 'paginate')) {
            $model = $model->paginate($this->perPage);
        } elseif ($this->isQueryBuilder($model)) {
            $model = $model->get();
        }

        return $model;
    }

    /**
     * Get rows from model instance.
     *
     * @param  $model
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function buildRowsFromModel($model)
    {
        $this->model = $model = $this->buildModel($model);

        if ($model instanceof Paginator) {
            $this->setRowsData($model->getItems());
            $this->paginate = true;
        } elseif ($model instanceof Arrayable) {
            $this->setRowsData($model->toArray());
        } elseif (is_array($model)) {
            $this->setRowsData($model);
        } else {
            throw new InvalidArgumentException("Unable to convert \$model to array.");
        }
    }

    /**
     * Set rows data.
     *
     * @param  array    $rows
     * @return array
     */
    protected function setRowsData(array $rows = array())
    {
        return $this->rows->data = $rows;
    }
    /**
     * Get rows collection.
     *
     * @return array
     */
    protected function query()
    {
        if (empty($this->rows->data)) {
            $this->buildRowsFromModel($this->model);
        }

        return $this->rows->data;
    }

    /**
     * Resolve query builder from model instance.
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     * @throws \InvalidArgumentException
     */
    protected function resolveQueryBuilderFromModel()
    {
        $model = $this->model;

        if (! $this->isQueryBuilder($model)) {
            throw new InvalidArgumentException("Unable to load Query Builder from \$model");
        }

        return $model;
    }

    /**
     * Check if given $model is a query builder.
     *
     * @param  mixed    $model
     * @return bool
     */
    protected function isQueryBuilder($model)
    {
        return ($model instanceof QueryBuilder || $model instanceof EloquentBuilder);
    }
}
