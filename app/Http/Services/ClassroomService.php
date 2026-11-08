<?php

namespace App\Http\Services;

use App\Http\Repositories\ClassroomRepository;

class ClassroomService extends BaseService
{
    public function __construct(private ClassroomRepository $classroomRepository)
    {
        parent::__construct($classroomRepository);
    }

    public function getWithFilters(array $data)
    {
        return $this->classroomRepository->getWithFilters($data);
    }

    public function store(array $data)
    {
        return parent::store($data)->load('course');
    }

    public function update(array $data, string $id)
    {
        return parent::update($data, $id)->load('course');
    }

    public function manageUser(string $id, array $data)
    {
        return $this->classroomRepository->manageUser($id, (int) $data['user_id'], $data['action']);
    }
}
