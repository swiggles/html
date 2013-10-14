<?php namespace Orchestra\Html\Table;

use Closure;
use InvalidArgumentException;
use Illuminate\Support\Fluent;
use Illuminate\Pagination\Paginator;
use Orchestra\Html\AbstractableGrid;

class Grid extends AbstractableGrid {

	/**
	 * List of rows in array, is used when model is null.
	 *
	 * @var array
	 */
	protected $rows = null;

	/**
	 * Eloquent model used for table.
	 *
	 * @var mixed
	 */
	protected $model = null;

	/**
	 * All the columns.
	 *
	 * @var array
	 */
	protected $columns = array();

	/**
	 * Key map for column overwriting.
	 *
	 * @var array
	 */
	protected $keyMap = array();

	/**
	 * Enable to attach pagination during rendering.
	 *
	 * @var boolean
	 */
	protected $paginate = false;

	/**
	 * Set the no record message.
	 *
	 * @var string
	 */
	public $empty = null;

	/**
	 * Selected view path for table layout.
	 *
	 * @var array
	 */
	protected $view = null;

	/**
	 * {@inheritdoc}
	 */
	protected function initiate()
	{
		$config = $this->app['config']->get('orchestra/html::table', array());
		
		foreach ($config as $key => $value)
		{
			if ( ! property_exists($this, $key)) continue;

			$this->{$key} = $value;
		}
		
		$this->rows = new Fluent(array(
			'data'       => array(),
			'attributes' => function () { return array(); },
		));
	}

	/**
	 * Attach Eloquent as row and allow pagination (if required).
	 *
	 * <code>
	 *		// add model without pagination
	 *		$table->with(User::all());
	 *
	 *		// add model with pagination
	 *		$table->with(User::paginate(30), true);
	 * </code>
	 *
	 * @param  \Illuminate\Database\Eloquent\Model  $model
	 * @param  boolean                              $paginate
	 * @return void
	 */
	public function with($model, $paginate = true)
	{
		$this->paginate = $paginate;
		$this->model    = $model;

		if ($paginate and ( ! $model instanceof Paginator))
		{
			$model = $model->paginate();
		}

		$this->rows(true === $paginate ? $model->getItems() : $model);
	}

	/**
	 * Set table layout (view).
	 *
	 * <code>
	 *		// use default horizontal layout
	 *		$table->layout('horizontal');
	 *
	 * 		// use default vertical layout
	 * 		$table->layout('vertical');
	 *
	 *		// define table using custom view
	 *		$table->layout('path.to.view');
	 * </code>
	 *
	 * @param  string   $name
	 * @return void
	 */
	public function layout($name)
	{
		switch ($name)
		{
			case 'horizontal' :
			case 'vertical' :
				$this->view = "orchestra/html::table.{$name}";
				break;
			default :
				$this->view = $name;
				break;
		}
	}

	/**
	 * Attach rows data instead of assigning a model.
	 *
	 * <code>
	 *		// assign a data
	 * 		$table->rows(DB::table('users')->get());
	 * </code>
	 *
	 * @param  array    $rows
	 * @return void
	 */
	public function rows(array $rows = null)
	{
		if (is_null($rows)) return $this->rows->data;

		$this->rows->data = $rows;
	}

	/**
	 * Append a new column to the table.
	 *
	 * <code>
	 *		// add a new column using just field name
	 *		$table->column('username');
	 *
	 *		// add a new column using a label (header title) and field name
	 *		$table->column('User Name', 'username');
	 *
	 *		// add a new column by using a field name and closure
	 *		$table->column('fullname', function ($column)
	 *		{
	 *			$column->label = 'User Name';
	 *			$column->value = function ($row) { 
	 * 				return $row->first_name.' '.$row->last_name; 
	 * 			};
	 *
	 * 			$column->attributes(function ($row) { 
	 *				return array('data-id' => $row->id);
	 *			});
	 *		});
	 * </code>
	 *
	 * @param  mixed    $label
	 * @param  mixed    $callback
	 * @return \Illuminate\Support\Fluent
	 */
	public function column($name, $callback = null)
	{
		$value = '';
		$label = $name;

		switch (true)
		{
			case ! is_string($label) :
				$callback = $name;
				$name     = '';	
				$label    = '';
				break;
			case is_string($callback) :
				$name     = mb_strtolower($callback);
				$callback = null; 
				break;
			default :
				$name  = mb_strtolower($name);
				$label = ucwords($name);
				break;
		}

		if ( ! empty($name))
		{
			$value = function ($row) use ($name) { return $row->{$name}; };
		}
		
		$column = new Fluent(array(
			'id'         => $name,
			'label'      => $label,
			'value'      => $value,
			'headers'    => array(),
			'attributes' => function ($row) { return array(); },
		));

		// run closure
		if (is_callable($callback)) call_user_func($callback, $column);

		$this->columns[]      = $column;
		$this->keyMap[$name] = count($this->columns) - 1;

		return $column;
	}

	/**
	 * Allow column overwriting.
	 *
	 * @param  string   $name
	 * @param  mixed    $callback
	 * @return \Illuminate\Support\Fluent
	 * @throws \InvalidArgumentException
	 */
	public function of($name, $callback = null)
	{
		if ( ! isset($this->keyMap[$name]))
		{
			throw new InvalidArgumentException("Column name [{$name}] is not available.");
		}

		$id = $this->keyMap[$name];

		if (is_callable($callback)) call_user_func($callback, $this->columns[$id]);

		return $this->columns[$id];
	}

	/**
	 * {@inheritdoc}
	 */
	public function __call($method, array $parameters = array())
	{
		if ( ! in_array($method, array('columns', 'view')))
		{
			throw new InvalidArgumentException("Unable to use __call for [{$method}].");
		}

		return $this->$method;
	}

	/**
	 * {@inheritdoc}
	 */
	public function __get($key)
	{
		if ( ! in_array($key, array('attributes', 'columns', 'model', 'paginate', 'view', 'rows')))
		{
			throw new InvalidArgumentException("Unable to use __get for [{$key}].");
		}
		
		return $this->{$key};
	}

	/**
	 * {@inheritdoc}
	 */
	public function __set($key, $values)
	{
		if ( ! in_array($key, array('attributes')))
		{
			throw new InvalidArgumentException("Unable to use __set for [{$key}].");
		}
		elseif ( ! is_array($values))
		{
			throw new InvalidArgumentException("Require values to be an array.");
		}

		$this->attributes($values, null);
	}

	/**
	 * {@inheritdoc}
	 */
	public function __isset($key)
	{
		if ( ! in_array($key, array('attributes', 'columns', 'model', 'paginate', 'view')))
		{
			throw new InvalidArgumentException("Unable to use __isset for [{$key}].");
		}

		return isset($this->{$key});
	}
}
