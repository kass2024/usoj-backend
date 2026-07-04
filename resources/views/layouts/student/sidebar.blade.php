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
                    <x-nav-link class="nav-link menu-link" :href="route('student.dashboard')" :active="request()->routeIs('student.dashboard')">
                        <i class="ri-dashboard-2-line"></i> <span data-key="t-dashboards">Dashboards</span>
                    </x-nav-link>

                </li>
                        <li class="nav-item">
                    <x-nav-link class="nav-link menu-link" :href="route('student.courses.index')" :active="request()->routeIs('student.courses.index') 
                       ">
                        <i class="ri-book-open-line"></i> <span data-key="t-exams">Courses</span>
                    </x-nav-link>

                </li>

                <li class="nav-item">
                    <x-nav-link class="nav-link menu-link" :href="route('student.quizzes.index')" :active="request()->routeIs('student.quizzes.index') ||
                        request()->routeIs('student.quizzes.submission') ||
                        request()->routeIs('student.quizzes.view_submission')">
                        <i class="ri-question-answer-line"></i> <span data-key="t-quizzes">Quizzes</span>
                    </x-nav-link>

                </li>
                <li class="nav-item">
                    <x-nav-link class="nav-link menu-link" :href="route('student.assignments.index')" :active="request()->routeIs('student.assignments.index') ||
                        request()->routeIs('student.assignments.submission') ||
                        request()->routeIs('student.assignments.view_submission')">
                        <i class="ri-task-line"></i> <span data-key="t-assignments">Assignments</span>
                    </x-nav-link>

                </li>
                <li class="nav-item">
                    <x-nav-link class="nav-link menu-link" :href="route('student.exams.index')" :active="request()->routeIs('student.exams.index') ||
                        request()->routeIs('student.exams.submission') ||
                        request()->routeIs('student.exams.view_submission')">
                        <i class="ri-pencil-ruler-line"></i> <span data-key="t-exams">Exams</span>
                    </x-nav-link>

                </li>
                 <li class="nav-item">
                    <x-nav-link class="nav-link menu-link" :href="route('student.marks.index')" :active="request()->routeIs('student.marks.index') 
                       ">
                        <i class="ri-bar-chart-2-line"></i> <span data-key="t-exams">View Marks</span>
                    </x-nav-link>

                </li>
         

    

            </ul>
        </div>
        <!-- Sidebar -->
    </div>

    <div class="sidebar-background"></div>
</div>
