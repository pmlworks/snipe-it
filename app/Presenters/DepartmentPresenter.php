<?php

namespace App\Presenters;

/**
 * Class DepartmentPresenter
 */
class DepartmentPresenter extends Presenter
{


    public function formattedNameLink() {

        if (auth()->user()->can('department.view', $this)) {
            return ($this->tag_color ? "<i class='fa-solid fa-fw fa-square' style='color: ".e($this->tag_color)."' aria-hidden='true'></i> " : '').' <a href="'.route('departments.show', e($this->id)).'">'.e($this->name).'</a>';
        }

        return ($this->tag_color ? "<i class='fa-solid fa-fw fa-square' style='color: ".e($this->tag_color)."' aria-hidden='true'></i> " : '').$this->name;
    }
}
