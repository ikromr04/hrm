<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EmployeeDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $this->get('/employees')->assertRedirect('/login');
    }

    public function test_directory_lists_the_first_page_with_tab_counts()
    {
        $this->actingAs(User::factory()->create());

        $this->get('/employees')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('employees/index')
                ->has('employees.data', 10)
                ->where('employees.total', 30)
                ->where('employees.last_page', 3)
                ->where('counts.all', 30)
                ->where('counts.probation', 4)
                ->where('summary.departments', 7)
            );
    }

    public function test_status_tab_filters_the_list()
    {
        $this->actingAs(User::factory()->create());

        $this->get('/employees?status=probation')
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.status', 'probation')
                ->has('employees.data', 4)
                ->where('employees.data.0.status', 'probation')
            );
    }

    public function test_dropdown_filters_narrow_the_list_and_the_tab_counts()
    {
        $this->actingAs(User::factory()->create());

        $this->get('/employees?department=Разработка&location=Душанбе')
            ->assertInertia(fn (Assert $page) => $page
                ->where('counts.all', 7)
                ->where('counts.dismissed', 1)
                ->has('employees.data', 7)
            );
    }

    public function test_second_page_is_available()
    {
        $this->actingAs(User::factory()->create());

        $this->get('/employees?page=2')
            ->assertInertia(fn (Assert $page) => $page
                ->where('employees.current_page', 2)
                ->has('employees.data', 10)
            );
    }

    public function test_unknown_status_is_rejected()
    {
        $this->actingAs(User::factory()->create());

        $this->get('/employees?status=fired')->assertSessionHasErrors('status');
    }
}
