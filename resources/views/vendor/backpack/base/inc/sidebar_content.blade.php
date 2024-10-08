<!-- This file is used to store sidebar items, starting with Backpack\Base 0.9.0 -->
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('dashboard') }}"><i class="la la-home nav-icon"></i> {{ trans('backpack::base.dashboard') }}</a></li>

<li class="nav-item nav-dropdown">
    <a class="nav-link nav-dropdown-toggle" href="#"><i class="nav-icon la la-lg la-users"></i> Customers</a>
    <ul class="nav-dropdown-items">
        <li class='nav-item'><a class='nav-link' href='{{ backpack_url('customer') }}'><i class='nav-icon la la-users'></i> Customers</a></li>
    </ul>
</li>

<li class="nav-item nav-dropdown">
    <a class="nav-link nav-dropdown-toggle" href="#"><i class="nav-icon la la-lg la-user"></i> Administrators</a>
    <ul class="nav-dropdown-items">
        <li class='nav-item'><a class='nav-link' href='{{ backpack_url('admin') }}'><i class='nav-icon la la-users'></i> Admins</a></li>
    </ul>
</li>

<li class="nav-item nav-dropdown">
    <a class="nav-link nav-dropdown-toggle" href="#"><i class="nav-icon la la-lg la-shopping-cart"></i> Researched Items</a>
    <ul class="nav-dropdown-items">
        <li class='nav-item'><a class='nav-link' href='{{ backpack_url('category') }}'><i class='nav-icon la la-archive'></i> Categories</a></li>
        <li class='nav-item'><a class='nav-link' href='{{ backpack_url('research-product') }}'><i class='nav-icon la la-shopping-cart'></i> Items</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ backpack_url('currency') }}"><i class="nav-icon la la-dollar-sign"></i> Currencies</a></li>
    </ul>
</li>

<li class="nav-item nav-dropdown">
    <a class="nav-link nav-dropdown-toggle" href="#"><i class="nav-icon la la-lg la-folder"></i> Pawn / Sell</a>
    <ul class="nav-dropdown-items">
        <li class="nav-item"><a class="nav-link" href="{{ backpack_url('user-pawns') }}"><i class="nav-icon la la-folder"></i> Pawn Requests</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ backpack_url('user-sells') }}"><i class="nav-icon la la-shopping-cart"></i> Sell Requests</a></li>
    </ul>
</li>

<li class="nav-item nav-dropdown">
    <a class="nav-link nav-dropdown-toggle" href="#"><i class="nav-icon la la-lg la-briefcase"></i> Subscription</a>
    <ul class="nav-dropdown-items">
        <li class="nav-item"><a class="nav-link" href="{{ backpack_url('subscription-plans') }}"><i class="nav-icon la la-briefcase"></i> plans</a></li>
    </ul>
</li>

<li class="nav-item"><a class="nav-link" href="{{ backpack_url('withdrawal') }}"><i class="nav-icon la la-money"></i> Withdrawals</a></li>
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('transaction') }}"><i class="nav-icon la la-credit-card"></i> Transactions</a></li>
<!-- <li class="nav-item"><a class="nav-link" href="{{ backpack_url('faq') }}"><i class="nav-icon la la-question-circle"></i> Faqs</a></li> -->
<li class="nav-item nav-dropdown">
    <a class="nav-link nav-dropdown-toggle" href="#"><i class="nav-icon la la-lg la-sliders"></i> System</a>
    <ul class="nav-dropdown-items">
        <li class="nav-item"><a class="nav-link" href="{{ backpack_url('settings') }}"><i class="nav-icon la la-sliders"></i> Settings</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ backpack_url('promos') }}"><i class="nav-icon la la-gift"></i> Promos</a></li>
    </ul>
</li>