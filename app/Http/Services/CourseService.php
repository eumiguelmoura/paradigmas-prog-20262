<?php

namespace App\Http\Services;

use App\Http\Repositories\CourseRepository;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class CourseService extends BaseService
{
    public function __construct(private CourseRepository $courseRepository)
    {
        parent::__construct($courseRepository);
    }

    public function getWithFilters(array $data)
    {
        return $this->courseRepository->getWithFilters($data);
    }

    public function destroy(string $id)
    {
        if ($this->show($id)->classrooms()->exists()) {
            throw ValidationException::withMessages([
                'course' => 'Remova ou transfira as turmas antes de excluir o curso.',
            ]);
        }

        try {
            parent::destroy($id);
        } catch (QueryException $exception) {
            // A chave estrangeira também protege contra uma turma criada simultaneamente.
            if (in_array((string) $exception->getCode(), ['23000', '23503'])) {
                throw ValidationException::withMessages(['course' => 'O curso possui turmas vinculadas.']);
            }
            throw $exception;
        }
    }
}
