<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CourseClassroomTest extends TestCase
{
    use RefreshDatabase;

    private function authenticate(): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        return $user;
    }

    public function test_endpoints_require_authentication(): void
    {
        $this->getJson('/courses')->assertUnauthorized();
        $this->postJson('/classrooms/1/users', [])->assertUnauthorized();
    }

    public function test_course_crud_and_filter(): void
    {
        $this->authenticate();
        $id = $this->postJson('/courses', ['name' => 'Informática', 'description' => 'Curso técnico'])
            ->assertCreated()->assertJsonPath('data.description', 'Curso técnico')->json('data.id');
        $this->getJson('/courses?name=Inform')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/courses?name=Outro')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/courses/'.$id)->assertOk()->assertJsonPath('data.name', 'Informática');
        $this->putJson('/courses/'.$id, ['name' => 'TADS', 'description' => null])
            ->assertOk()->assertJsonPath('data.name', 'TADS');
        $this->patchJson('/courses/'.$id, ['description' => 'Atualizado'])
            ->assertOk()->assertJsonPath('data.description', 'Atualizado')->assertJsonPath('data.name', 'TADS');
        $this->deleteJson('/courses/'.$id)->assertNoContent();
        $this->getJson('/courses/'.$id)->assertNotFound();
    }

    public function test_invalid_course_and_missing_ids(): void
    {
        $this->authenticate();
        $this->postJson('/courses', ['name' => 'A'])->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->postJson('/courses', ['name' => str_repeat('x', 256)])->assertUnprocessable();
        $this->putJson('/courses/9999', ['name' => 'Curso'])->assertNotFound();
        $this->deleteJson('/courses/9999')->assertNotFound();
    }

    public function test_classrooms_include_course_and_courses_include_classrooms(): void
    {
        $this->authenticate();
        $course = Course::create(['name' => 'TADS']);
        $other = Course::create(['name' => 'Outro']);
        $id = $this->postJson('/classrooms', ['name' => 'Turma A', 'vacancies' => 30, 'course_id' => $course->id])
            ->assertCreated()->assertJsonPath('data.course.name', 'TADS')->json('data.id');
        $this->getJson('/classrooms?course_id='.$course->id)->assertOk()->assertJsonPath('data.0.course.id', $course->id);
        $this->getJson('/classrooms?course_id='.$other->id)->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/courses/'.$course->id)->assertOk()->assertJsonPath('data.classrooms.0.id', $id);
        $this->getJson('/classrooms/'.$id)->assertOk()->assertJsonPath('data.course.id', $course->id);
        $this->deleteJson('/courses/'.$course->id)->assertUnprocessable();
        $this->patchJson('/classrooms/'.$id, ['course_id' => $other->id])
            ->assertOk()->assertJsonPath('data.course.id', $other->id);
        $this->deleteJson('/courses/'.$course->id)->assertNoContent();
        $this->deleteJson('/classrooms/'.$id)->assertNoContent();
        $this->deleteJson('/courses/'.$other->id)->assertNoContent();
    }

    public function test_classroom_validation(): void
    {
        $this->authenticate();
        $this->postJson('/classrooms', ['name' => 'Turma A', 'vacancies' => 20])
            ->assertUnprocessable()->assertJsonValidationErrors('course_id');
        $this->postJson('/classrooms', ['name' => 'Turma A', 'vacancies' => 1.5, 'course_id' => 9999])
            ->assertUnprocessable()->assertJsonValidationErrors(['vacancies', 'course_id']);
    }

    public function test_many_to_many_add_remove_is_idempotent_and_cascades(): void
    {
        $this->authenticate();
        $student = User::factory()->create();
        $otherStudent = User::factory()->create();
        $course = Course::create(['name' => 'TADS']);
        $a = Classroom::create(['name' => 'Turma A', 'vacancies' => 20, 'course_id' => $course->id]);
        $b = Classroom::create(['name' => 'Turma B', 'vacancies' => 20, 'course_id' => $course->id]);
        foreach ([$a, $a, $b] as $classroom) {
            $this->postJson('/classrooms/'.$classroom->id.'/users', ['user_id' => $student->id, 'action' => 'add'])
                ->assertOk()->assertJsonPath('data.users.0.id', $student->id)->assertJsonMissingPath('data.users.0.password');
        }
        $this->postJson('/classrooms/'.$a->id.'/users', ['user_id' => $otherStudent->id, 'action' => 'add'])->assertOk();
        $this->assertDatabaseCount('classroom_user', 3);
        $this->assertCount(2, $student->classrooms);
        foreach ([1, 2] as $attempt) {
            $this->postJson('/classrooms/'.$a->id.'/users', ['user_id' => $student->id, 'action' => 'remove'])->assertOk();
        }
        $this->assertDatabaseCount('classroom_user', 2);
        $this->deleteJson('/classrooms/'.$b->id)->assertNoContent();
        $this->assertDatabaseCount('classroom_user', 1);
        $otherStudent->delete();
        $this->assertDatabaseCount('classroom_user', 0);
    }

    public function test_enrollment_validates_user_action_and_classroom(): void
    {
        $user = $this->authenticate();
        $course = Course::create(['name' => 'TADS']);
        $classroom = Classroom::create(['name' => 'Turma A', 'vacancies' => 20, 'course_id' => $course->id]);
        $this->postJson('/classrooms/'.$classroom->id.'/users', ['user_id' => 9999, 'action' => 'invalid'])
            ->assertUnprocessable()->assertJsonValidationErrors(['user_id', 'action']);
        $this->postJson('/classrooms/9999/users', ['user_id' => $user->id, 'action' => 'add'])->assertNotFound();
    }
}
