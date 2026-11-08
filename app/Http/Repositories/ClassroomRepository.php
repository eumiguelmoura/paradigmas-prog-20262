<?php

namespace App\Http\Repositories;

use App\Models\Classroom;

class ClassroomRepository extends BaseRepository
{
    public function __construct(private Classroom $model)
    {
        parent::__construct($model);
    }

    public function getWithFilters(array $data)
    {
        $builder = $this->model->query()->with('course')->where(function ($query) use ($data) {
            if (data_get($data, 'course_id')) {
                $query->where('course_id', $data['course_id']);
            }
            if (data_get($data, 'name')) {
                $query->where('name', 'like', '%'.$data['name'].'%');
            }
        });

        return $this->index($builder);
    }

    public function show(string $id)
    {
        return parent::show($id)->load(['course', 'users']);
    }

    public function manageUser(string $id, int $userId, string $action)
    {
        $classroom = $this->model->findOrFail($id);
        if ($action === 'add') {
            $classroom->users()->syncWithoutDetaching([$userId]);
        } else {
            $classroom->users()->detach($userId);
        }

        return $classroom->load(['course', 'users']);
    }
}
