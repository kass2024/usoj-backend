<div class="app-menu navbar-menu">
    <!-- LOGO -->
    <div class="navbar-brand-box">
        <!-- Dark Logo-->
        <a href="{{ route('dashboard') }}" class="logo logo-dark">
            <span class="logo-sm">
                <img src="/images/usj-crest.png" alt="" height="22">
            </span>
            <span class="logo-lg">
                <img src="/images/usj-crest.png" alt="" height="17">
            </span>
        </a>
        <!-- Light Logo-->
        <a href="{{ route('dashboard') }}" class="logo logo-light">
            <span class="logo-sm">
                <img src="/images/usj-crest.png" alt="" height="22">
            </span>
            <span class="logo-lg">
                <img src="/images/usj-crest.png" alt="" height="17">
            </span>
        </a>
        <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover"
                id="vertical-hover">
            <i class="ri-record-circle-line"></i>
        </button>
    </div>

    <div id="scrollbar">
        <div class="container-fluid">

            <div id="two-column-menu">
            </div>
            <ul class="navbar-nav" id="navbar-nav">
                <li class="menu-title"><span data-key="t-menu">Menu</span></li>
                <li class="nav-item">
                    <x-nav-link class="nav-link menu-link" :href="route('dashboard')"
                                :active="request()->routeIs('dashboard')">
                        <i class="ri-dashboard-2-line"></i> <span data-key="t-dashboards">Dashboards</span>
                    </x-nav-link>

                </li> <!-- end Dashboard Menu -->
                @if (in_array(Auth::user()->role, ['admin', 'super_admin']))

                    <li class="nav-item">
                        <a class="nav-link menu-link {{ Route::is('users.*') ? 'active' : '' }}" href="#users"
                           data-bs-toggle="collapse" role="button"
                           aria-expanded="{{ Route::is('users.*') ? 'true' : 'false' }}" aria-controls="users">
                            <i class="ri-user-line"></i> <span data-key="t-users">Users</span>
                        </a>
                        <div class="menu-dropdown collapse {{ Route::is('users.*') ? 'show' : '' }}" id="users">
                            <ul class="nav nav-sm flex-column">
                                <li class="nav-item">
                                    <a href="{{ route('users.index') }}"
                                       class="nav-link {{ Route::is('users.index') ? 'active' : '' }}"
                                       data-key="t-staffs">Staffs</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('users.heads_of_departments') }}"
                                       class="nav-link {{ Route::is('users.heads_of_departments') ? 'active' : '' }}"
                                       data-key="t-hod">Head of Departments</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('users.lectures') }}"
                                       class="nav-link {{ Route::is('users.lectures') ? 'active' : '' }}"
                                       data-key="t-lectures">Lectures</a>
                                </li>
                            </ul>
                        </div>
                    </li>

                 
                     
                  <li class="nav-item">
    <x-nav-link class="nav-link menu-link" :href="route('students.index')"
                :active="request()->routeIs('students.index')">
        <i class="ri-graduation-cap-line"></i>
        <span data-key="t-dashboards">Students</span>
    </x-nav-link>
</li>

                  <li class="nav-item">
                    <x-nav-link class="nav-link menu-link" :href="route('courses.index')"
                                :active="request()->routeIs('courses.index')">
                        <i class="ri-book-open-line"></i> <span data-key="t-dashboards">Courses</span>
                    </x-nav-link>

                </li>

                    <li class="menu-title"><span data-key="t-menu">Settings</span></li>
                    <li class="nav-item">
                        <a class="nav-link menu-link {{ request()->routeIs('settings.*') ? 'active' : '' }}"
                           href="#settings" data-bs-toggle="collapse" role="button"
                           aria-expanded="{{ request()->routeIs('settings.*') ? 'true' : 'false' }}"
                           aria-controls="settings">
                            <i class="ri-settings-2-line"></i> <span data-key="t-settings">Settings</span>
                        </a>
                        <div class="menu-dropdown collapse {{ request()->routeIs('settings.*') ? 'show' : '' }}"
                             id="settings">
                            <ul class="nav nav-sm flex-column">
                                <li class="nav-item">
                                    <x-nav-link class="nav-link" :href="route('settings.programs.index')"
                                                :active="request()->routeIs('settings.programs.index')">
                                        <span data-key="t-program">Programs</span>
                                    </x-nav-link>
                                </li>
                                <li class="nav-item">
                                    <x-nav-link class="nav-link" :href="route('settings.degree-levels.index')"
                                                :active="request()->routeIs('settings.degree-levels.index')">
                                        <span data-key="t-degree-levels">Degree Levels</span>
                                    </x-nav-link>
                                </li>
                                <li class="nav-item">
                                    <x-nav-link class="nav-link" :href="route('settings.schools.index')"
                                                :active="request()->routeIs('settings.schools.index')">
                                        <span data-key="t-schools">Schools</span>
                                    </x-nav-link>
                                </li>
                                <li class="nav-item">
                                    <a href="#departmentSidebar"
                                       class="nav-link {{ request()->routeIs('settings.departments.*') ? 'active' : '' }}"
                                       data-bs-toggle="collapse" role="button"
                                       aria-expanded="{{ request()->routeIs('settings.departments.*') ? 'true' : 'false' }}"
                                       aria-controls="departmentSidebar" data-key="t-departments">
                                        Departments
                                    </a>
                                    <div class="collapse menu-dropdown {{ request()->routeIs('settings.departments.*') ? 'show' : '' }}"
                                         id="departmentSidebar">
                                        <ul class="nav nav-sm flex-column">
                                            @foreach (App\Models\School::all() as $school)
                                                <li class="nav-item">
                                                    <a href="{{ route('settings.departments.show', $school->id) }}"
                                                       class="nav-link {{ request()->routeIs('settings.departments.show') && request()->route('department') == $school->id ? 'active' : '' }}"
                                                       data-key="t-{{ $school->slug }}">
                                                        {{ $school->name }}
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </li>
                                <li class="nav-item">
                                    <a href="#classesSidebar"
                                       class="nav-link {{ request()->routeIs('settings.classes.*') ? 'active' : '' }}"
                                       data-bs-toggle="collapse" role="button"
                                       aria-expanded="{{ request()->routeIs('settings.classes.*') ? 'true' : 'false' }}"
                                       aria-controls="classesSidebar" data-key="t-classes">
                                        Classes
                                    </a>
                                    <div class="collapse menu-dropdown {{ request()->routeIs('settings.classes.*') ? 'show' : '' }}"
                                         id="classesSidebar">
                                        <ul class="nav nav-sm flex-column">
                                            @foreach (App\Models\School::all() as $school)
                                                <li class="nav-item">
                                                    <a href="{{ route('settings.classes.show', $school->id) }}"
                                                       class="nav-link {{ request()->routeIs('settings.classes.show') && request()->route('class') == $school->id ? 'active' : '' }}"
                                                       data-key="t-{{ $school->slug }}">
                                                        {{ $school->name }}
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <x-nav-link class="nav-link" :href="route('settings.academic-years.index')"
                                                :active="request()->routeIs('settings.academic-years.index')">
                                        <span data-key="t-academic">Academic Year</span>
                                    </x-nav-link>
                                </li>
                            </ul>
                        </div>
                    </li>



                @endif

                @if (Auth::user()->role === 'head_of_department')
                    <li class="menu-title"><span data-key="t-students">Students</span></li>

                    @foreach (App\Models\ClassYear::where('department_id', Auth::user()->department_id)->groupBy('degree_level_id')->get() as $item)
                        <li class="nav-item">
                            <a class="nav-link menu-link" href="#{{ $item->degree_level->slug }}" data-bs-toggle="collapse"
                               role="button" aria-expanded="false" aria-controls="{{ $item->degree_level->slug }}">
                                <i class="ri-arrow-right-s-line"></i> <span
                                      data-key="t-{{ $item->degree_level->slug }}">{{ $item->degree_level->name }}</span>
                            </a>
                            <div class="menu-dropdown collapse" id="{{ $item->degree_level->slug }}">
                                <ul class="nav nav-sm flex-column">
                                    @foreach (App\Models\ClassYear::where('department_id', $item->department_id)->where('degree_level_id', $item->degree_level_id)->get() as $class)
                                        <li class="nav-item">
                                            <a href="{{ route('hod.students.index', ['slug' => $item->degree_level->slug, 'year' => $class->id]) }}"
                                               class="nav-link" data-key="t-{{ $class->id }}">{{ $class->year_name }}</a>
                                        </li>
                                    @endforeach

                                </ul>
                            </div>
                        </li>
                    @endforeach
                    <li class="menu-title"><span data-key="t-modules">Modules</span></li>

                    @foreach (App\Models\ClassYear::where('department_id', Auth::user()->department_id)->groupBy('degree_level_id')->get() as $item)
                        <li class="nav-item">
                            <a class="nav-link menu-link" href="#{{ $item->degree_level->slug }}Modules"
                               data-bs-toggle="collapse" role="button" aria-expanded="false"
                               aria-controls="{{ $item->degree_level->slug }}Modules">
                                <i class="ri-arrow-right-s-line"></i> <span
                                      data-key="t-{{ $item->degree_level->slug }}Modules">{{ $item->degree_level->name }}</span>
                            </a>
                            <div class="menu-dropdown collapse" id="{{ $item->degree_level->slug }}Modules">
                                <ul class="nav nav-sm flex-column">
                                    @foreach (App\Models\ClassYear::where('department_id', $item->department_id)->where('degree_level_id', $item->degree_level_id)->get() as $class)
                                        <li class="nav-item">
                                            <a href="{{ route('hod.modules.index', ['slug' => $item->degree_level->slug, 'year' => $class->id]) }}"
                                               class="nav-link" data-key="t-{{ $class->id }}">{{ $class->year_name }}</a>
                                        </li>
                                    @endforeach

                                </ul>
                            </div>
                        </li>
                    @endforeach

                @endif
                @if (Auth::user()->role === 'lecture')
                    @foreach (App\Models\Modules::where('user_id', Auth::user()->id)->groupBy('class_year_id')->get() as $item)
                        <li class="nav-item">
                            <x-nav-link class="nav-link menu-link" :href="route('lecture.index', $item->class_year_id)"
                                        :active="request()->routeIs('lecture.index', $item->class_year_id)">
                                <i class="ri-menu-4-line"></i> <span
                                      data-key="t-{{ $item->class_year->year_name }}">{{ $item->class_year->year_name }}
                                    ({{ $item->class_year->department->abbr }})
                                </span>
                            </x-nav-link>
                        </li>
                    @endforeach
                @endif
  <li class="nav-item">
                        <a class="nav-link menu-link {{ Route::is('settings.courses.*') ? 'active' : '' }}"
                            href="#courses" data-bs-toggle="collapse" role="button"
                            aria-expanded="{{ Route::is('settings.courses.*') ? 'true' : 'false' }}"
                            aria-controls="courses">
                            <i class="ri-file-list-3-line"></i> <span data-key="t-courses">Courses</span>
                        </a>
                        <div class="menu-dropdown collapse {{ Route::is('settings.courses.*') ? 'show' : '' }}"
                            id="courses">
                            <ul class="nav nav-sm flex-column">
                                @foreach (App\Models\Program::whereHas('schools')->with('schools')->get() as $program)
                                    <li class="menu-title"><span data-key="t-menu">{{ $program->name }}</span></li>
                                    @foreach ($program->schools as $school)
                                        <li class="nav-item">
                                            <a href="#{{ $school->slug }}"
                                                class="nav-link {{ request()->is("settings/courses/school/{$school->id}*") ? 'active' : '' }}"
                                                data-bs-toggle="collapse" role="button"
                                                aria-expanded="{{ request()->is("settings/courses/school/{$school->id}*") ? 'true' : 'false' }}"
                                                aria-controls="{{ $school->slug }}"
                                                data-key="t-{{ $school->slug }}">
                                                {{ $school->name }}
                                            </a>
                                            <div class="collapse menu-dropdown {{ request()->is("settings/courses/school/{$school->id}*") ? 'show' : '' }}"
                                                id="{{ $school->slug }}">
                                                <ul class="nav nav-sm flex-column">
                                                    @foreach (App\Models\Department::where('school_id', $school->id)->get() as $department)
                                                        <li class="nav-item">
                                                            <a href="{{ route('settings.courses.show', $department->id) }}"
                                                                class="nav-link {{ request()->is("settings/courses/{$department->id}") ? 'active' : '' }}"
                                                                data-key="t-{{ $department->slug }}">
                                                                {{ $department->name }}
                                                            </a>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </li>
                                    @endforeach
                                @endforeach
                            </ul>
                        </div>
                    </li>
                <li class="nav-item">
                    <x-nav-link class="nav-link menu-link" :href="route('certificates.index')"
                                :active="request()->routeIs('certificates.index')">
                        <i class="ri-dashboard-2-line"></i> <span data-key="t-dashboards">Generate Academic Docs</span>
                    </x-nav-link>
                </li>
                <li class="nav-item">
                    <x-nav-link class="nav-link menu-link" :href="route('document-links.index')"
                                :active="request()->routeIs('document-links.*')">
                        <i class="ri-link-m"></i> <span data-key="t-doc-links">USJ Upload Links</span>
                    </x-nav-link>
                </li>
                
                
          

            </ul>
        </div>
        <!-- Sidebar -->
    </div>

    <div class="sidebar-background"></div>
</div>