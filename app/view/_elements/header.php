<?php // TODO: define state and show logged/starter nav ?>
<header>
    <nav class="deep-orange darken-4" role="navigation">
        <div class="nav-wrapper container">
            <a href="/" title="На главную" class="brand-logo">
                <img src="/static/img/logo.png" alt="MF">
            </a>

            <ul class="right hide-on-med-and-down">
                <li>
                    <a href="<?= router_get_path('login'); ?>" title="Войти на сайт"><i
                            class="material-icons mdi-action-perm-identity left"></i>Вход</a>
                </li>
            </ul>
            <ul id="nav-mobile" class="side-nav">
                <li>
                    <a href="<?= router_get_path('login'); ?>" title="Войти на сайт"><i
                            class="material-icons mdi-action-perm-identity left"></i>Вход</a>
                </li>
            </ul>
            <a href="javascript:" title="Меню" data-activates="nav-mobile" class="button-collapse"><i
                    class="material-icons mdi-navigation-menu"></i></a>
        </div>
    </nav>
</header>
