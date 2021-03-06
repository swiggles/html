<?php namespace Orchestra\Html\Table;

use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Translation\Translator;
use Orchestra\Html\Builder as BaseBuilder;
use Illuminate\Contracts\View\Factory as View;
use Orchestra\Contracts\Html\Grid as GridContract;
use Orchestra\Contracts\Html\Table\Builder as BuilderContract;

class TableBuilder extends BaseBuilder implements BuilderContract
{
    /**
     * {@inheritdoc}
     */
    public function __construct(Request $request, Translator $translator, View $view, GridContract $grid)
    {
        $this->request    = $request;
        $this->translator = $translator;
        $this->view       = $view;
        $this->grid       = $grid;
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        $grid = $this->grid;

        // Add paginate value for current listing while appending query string,
        // however we also need to remove ?page from being added twice.
        $input = Arr::except($this->request->query(), [$grid->pageName]);

        $rows = $grid->rows();

        $pagination = (true === $grid->paginated() ? $grid->model->appends($input) : '');

        $data = [
            'attributes' => [
                'row'   => $grid->rows->attributes,
                'table' => $grid->attributes,
            ],
            'columns'    => $grid->columns(),
            'empty'      => $this->translator->get($grid->empty),
            'grid'       => $grid,
            'pagination' => $pagination,
            'rows'       => $rows,
        ];

        // Build the view and render it.
        return $this->view->make($grid->view)->with($data)->render();
    }
}
