<?php namespace Orchestra\Html\Tests\Table;

use Mockery as m;
use Orchestra\Html\Table\Grid;

class GridTest extends \PHPUnit_Framework_TestCase {

	/**
	 * Teardown the test environment.
	 */
	public function tearDown()
	{
		m::close();
	}

	/**
	 * Test instanceof Orchestra\Html\Table\Grid.
	 *
	 * @test
	 */
	public function testInstanceOfGrid()
	{
		$stub = new Grid(array(
			'empty' => 'No data',
			'view'  => 'foo',
		));

		$refl  = new \ReflectionObject($stub);
		$empty = $refl->getProperty('empty');
		$view  = $refl->getProperty('view');

		$empty->setAccessible(true);
		$view->setAccessible(true);

		$this->assertInstanceOf('\Orchestra\Html\Table\Grid', $stub);
		$this->assertEquals('No data', $empty->getValue($stub));
		$this->assertEquals('foo', $view->getValue($stub));
	}

	/**
	 * Test Orchestra\Html\Table\Grid::with() method.
	 *
	 * @test
	 */
	public function testWithMethod()
	{
		$mock = array(new \Illuminate\Support\Fluent);
		$stub = new Grid(array());
		$stub->with($mock, false);

		$refl     = new \ReflectionObject($stub);
		$rows     = $refl->getProperty('rows');
		$model    = $refl->getProperty('model');
		$paginate = $refl->getProperty('paginate');

		$rows->setAccessible(true);
		$model->setAccessible(true);
		$paginate->setAccessible(true);

		$this->assertEquals($mock, $rows->getValue($stub)->data);
		$this->assertEquals($mock, $model->getValue($stub));
		$this->assertFalse($paginate->getValue($stub));
		$this->assertTrue(isset($stub->model));

		$paginator = m::mock('User');
		$paginator->shouldReceive('paginate')->once()->andReturn($paginator)
			->shouldReceive('getItems')->once()->andReturn(array('foo'));

		$stub2 = new Grid(array());
		$stub2->with($paginator);
	}

	/**
	 * Test Orchestra\Html\Table\Grid::layout() method.
	 *
	 * @test
	 */
	public function testLayoutMethod()
	{
		$stub = new Grid(array());

		$refl = new \ReflectionObject($stub);
		$view = $refl->getProperty('view');
		$view->setAccessible(true);

		$stub->layout('horizontal');
		$this->assertEquals('orchestra/html::table.horizontal', $view->getValue($stub));

		$stub->layout('vertical');
		$this->assertEquals('orchestra/html::table.vertical', $view->getValue($stub));

		$stub->layout('foo');
		$this->assertEquals('foo', $view->getValue($stub));
	}

	/**
	 * Test Orchestra\Html\Table\Grid::of() method.
	 *
	 * @test
	 */
	public function testOfMethod()
	{
		$stub = new Grid(array());
		$expected = array(
			new \Illuminate\Support\Fluent(array(
				'id'         => 'id',
				'label'      => 'Id',
				'value'      => 'Foobar',
				'header'     => array(),
				'attributes' => function ($row) { return array(); }
			)),
			new \Illuminate\Support\Fluent(array(
				'id'         => 'foo1',
				'label'      => 'Foo1',
				'value'      => 'Foo1 value',
				'header'     => array(),
				'attributes' => function ($row) { return array(); }
			)),
			new \Illuminate\Support\Fluent(array(
				'id'         => 'foo2',
				'label'      => 'Foo2',
				'value'      => 'Foo2 value',
				'header'     => array(),
				'attributes' => function ($row) { return array(); }
			))
		);

		$stub->column('id', function ($c)
		{
			$c->value('Foobar');
		});

		$stub->column(function ($c)
		{
			$c->id('foo1')->label('Foo1');
			$c->value('Foo1 value');
		});

		$stub->column('Foo2', 'foo2')->value('Foo2 value');

		$stub->attributes = array('class' => 'foo');

		$output = $stub->of('id');

		$this->assertEquals('Foobar', $output->value);
		$this->assertEquals('Id', $output->label);
		$this->assertEquals('id', $output->id);

		$this->assertEquals(array('class' => 'foo'), $stub->attributes);
		$this->assertEquals($expected, $stub->columns());
	}

	/**
	 * Test Orchestra\Html\Table\Grid::of() method throws exception.
	 *
	 * @expectedException \InvalidArgumentException
	 */
	public function testOfMethodThrowsException()
	{
		$stub = new Grid(array());
		$output = $stub->of('id');
	}

	/**
	 * Test Orchestra\Html\Table\Grid::attributes() method.
	 *
	 * @test
	 */
	public function testAttributesMethod()
	{
		$stub = new Grid(array());

		$refl       = new \ReflectionObject($stub);
		$attributes = $refl->getProperty('attributes');
		$attributes->setAccessible(true);

		$stub->attributes(array('class' => 'foo'));

		$this->assertEquals(array('class' => 'foo'), $attributes->getValue($stub));
		$this->assertEquals(array('class' => 'foo'), $stub->attributes());

		$stub->attributes('id', 'foobar');

		$this->assertEquals(array('id' => 'foobar', 'class' => 'foo'), $attributes->getValue($stub));
		$this->assertEquals(array('id' => 'foobar', 'class' => 'foo'), $stub->attributes());
	}

	/**
	 * Test Orchestra\Html\Table\Grid magic method __call() throws 
	 * exception.
	 *
	 * @expectedException \InvalidArgumentException
	 */
	public function testMagicMethodCallThrowsException()
	{
		$stub = new Grid(array());
		$stub->invalidMethod();
	}

	/**
	 * Test Orchestra\Html\Table\Grid magic method __get() throws 
	 * exception.
	 *
	 * @expectedException \InvalidArgumentException
	 */
	public function testMagicMethodGetThrowsException()
	{
		$stub = new Grid(array());
		$invalid = $stub->invalidProperty;
	}

	/**
	 * Test Orchestra\Html\Table\Grid magic method __set() throws 
	 * exception.
	 *
	 * @expectedException \InvalidArgumentException
	 */
	public function testMagicMethodSetThrowsException()
	{
		$stub = new Grid(array());
		$stub->invalidProperty = array('foo');
	}

	/**
	 * Test Orchestra\Html\Table\Grid magic method __set() throws 
	 * exception when $values is not an array.
	 *
	 * @expectedException \InvalidArgumentException
	 */
	public function testMagicMethodSetThrowsExceptionValuesNotAnArray()
	{
		$stub = new Grid(array());
		$stub->attributes = 'foo';
	}

	/**
	 * Test Orchestra\Html\Table\Grid magic method __isset() throws 
	 * exception.
	 *
	 * @expectedException \InvalidArgumentException
	 */
	public function testMagicMethodIssetThrowsException()
	{
		$stub = new Grid(array());
		$invalid = isset($stub->invalidProperty) ? true : false;
	}
}