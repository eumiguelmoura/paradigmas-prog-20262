<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseRequest;
use App\Http\Resources\CourseResource;
use App\Http\Services\CourseService;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CourseController extends Controller
{
    public function __construct(private CourseService $courseService) {}

    #[OA\Get(path: '/courses', summary: 'Lista cursos e suas turmas', security: [['sanctum' => []]], tags: ['Cursos'], responses: [new OA\Response(response: 200, description: 'Operação realizada'), new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated')])]
    public function index(Request $request)
    {
        return CourseResource::collection($this->courseService->getWithFilters($request->all()));
    }

    #[OA\Post(path: '/courses', summary: 'Cria um curso', security: [['sanctum' => []]], tags: ['Cursos'], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['name'], properties: [new OA\Property(property: 'name', type: 'string', example: 'TADS'), new OA\Property(property: 'description', type: 'string', nullable: true)])), responses: [new OA\Response(response: 201, description: 'Operação realizada'), new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated')])]
    public function store(CourseRequest $request)
    {
        return new CourseResource($this->courseService->store($request->validated()));
    }

    #[OA\Get(path: '/courses/{id}', summary: 'Exibe um curso e suas turmas', security: [['sanctum' => []]], tags: ['Cursos'], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Operação realizada'), new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated')])]
    public function show(string $id)
    {
        return new CourseResource($this->courseService->show($id));
    }

    #[OA\Put(path: '/courses/{id}', summary: 'Atualiza um curso', security: [['sanctum' => []]], tags: ['Cursos'], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['name'], properties: [new OA\Property(property: 'name', type: 'string', example: 'TADS'), new OA\Property(property: 'description', type: 'string', nullable: true)])), responses: [new OA\Response(response: 200, description: 'Operação realizada'), new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated')])]
    public function update(CourseRequest $request, string $id)
    {
        return new CourseResource($this->courseService->update($request->validated(), $id));
    }

    #[OA\Delete(path: '/courses/{id}', summary: 'Exclui um curso sem turmas', security: [['sanctum' => []]], tags: ['Cursos'], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 204, description: 'Operação realizada'), new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated')])]
    public function destroy(string $id)
    {
        $this->courseService->destroy($id);

        return response()->noContent();
    }
}
