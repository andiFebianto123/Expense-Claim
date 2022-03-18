<!-- This file is used to store sidebar items, starting with Backpack\Base 0.9.0 -->
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('dashboard') }}"><i class="la la-home nav-icon"></i> {{ trans('backpack::base.dashboard') }}</a></li>

@php
    $user = backpack_user();
    $role = $user->role->name ?? null;
    $department = $user->department->name ?? null;
    
    $classRole = 'App\Models\Role';
    $allowMaster = in_array($role, [$classRole::SUPER_ADMIN, $classRole::ADMIN, $classRole::DIRECTOR]);
    $allowLevelOne = in_array($role, [$classRole::SUPER_ADMIN, $classRole::ADMIN, $classRole::NATIONAL_SALES]);
    $allowLevelTwo = in_array($role, [$classRole::SUPER_ADMIN, $classRole::ADMIN, $classRole::DIRECTOR]);
    $allowAll = in_array($role, [$classRole::USER, $classRole::ADMIN, $classRole::GOA_HOLDER, $classRole::HOD, $classRole::SECRETARY]);
    $allowFinance = in_array($role, [$classRole::SUPER_ADMIN, $classRole::ADMIN, $classRole::DIRECTOR]);
@endphp


@if ($allowMaster)
    <li class="nav-title">Master</li>
    <li class="nav-item"><a class="nav-link" href="{{backpack_url('user-access-control')}}"><i class="la la-users nav-icon"></i> User Access Control</a></li>
    <li class="nav-item"><a class="nav-link" href="{{backpack_url('approval-card')}}"><i class="la la-cc-mastercard nav-icon"></i> Approval Card</a></li>    
@endif

<li class="nav-title">Master</li>
@foreach ((new App\Helpers\Sidebar())->generate() as $key => $menu)
    @if(in_array($role, $menu['access']))
    <li class="nav-item @if($menu['childrens']) nav-dropdown @endif">
        <a class="nav-link parent @if($menu['childrens']) nav-dropdown-toggle @endif" href="{{  $menu['url'] }}">
            <i class="nav-icon la {{$menu['icon']}}"></i> {{$menu['label']}}
        </a>
        @if($menu['childrens'])
        <ul class="nav-dropdown-items">
            @foreach($menu['childrens'] as $key2 => $child)
            <li class="nav-item">
                <a class="nav-link childs" href="{{ $child['url'] }}">
                <span>• {{$child['label']}}</span>
                </a>
            </li>
            @endforeach
        </ul>
        @endif
    </li> 
    @endif
@endforeach

@if($allowAll)
<li class="nav-title">Expense</li>
<li class="nav-item nav-dropdown"><a class="nav-link nav-dropdown-toggle" href="#"><i class="nav-icon la la-envelope-square"></i> User Request</a>
    <ul class="nav-dropdown-items">
        <li class="nav-item"><a class="nav-link" href="{{backpack_url('expense-user-request')}}"> Ongoing</a></li>
        <li class="nav-item"><a class="nav-link" href="{{backpack_url('expense-user-request-history')}}"> History</a></li>
    </ul>
</li>
@endif

@if ($allowLevelOne)
<li class="nav-item nav-dropdown"><a class="nav-link nav-dropdown-toggle" href="#"><i class="nav-icon la la-angle-right"></i> Approver HoD</a>
    <ul class="nav-dropdown-items">
        <li class="nav-item"><a class="nav-link" href="{{backpack_url('expense-approver-hod')}}"> Ongoing</a></li>
        <li class="nav-item"><a class="nav-link" href="{{backpack_url('expense-approver-hod-history')}}"> History</a></li>
    </ul>
</li>
@endif


@if ($allowLevelTwo)
<li class="nav-item nav-dropdown"><a class="nav-link nav-dropdown-toggle" href="#"><i class="nav-icon la la-angle-double-right"></i> Approver GoA</a>
    <ul class="nav-dropdown-items">
        <li class="nav-item"><a class="nav-link" href="{{backpack_url('expense-approver-goa')}}"> Ongoing</a></li>
        <li class="nav-item"><a class="nav-link" href="{{backpack_url('expense-approver-goa-history')}}"> History</a></li>
    </ul>
</li>    
@endif

@if ($allowFinance)
<li class="nav-item nav-dropdown"><a class="nav-link nav-dropdown-toggle" href="#"><i class="nav-icon la la-money"></i> Finance AP</a>
    <ul class="nav-dropdown-items">
        <li class="nav-item"><a class="nav-link" href="{{backpack_url('expense-finance-ap')}}"> Ongoing</a></li>
        <li class="nav-item"><a class="nav-link" href="{{backpack_url('expense-finance-ap-history')}}"> History</a></li>
    </ul>
</li>
@endif