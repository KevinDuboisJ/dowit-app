<style>
    /* Filament hides page breadcrumbs on small screens by default. */
    .fi-header .fi-breadcrumbs {
        display: block;
    }

    .fi-header > div:first-child,
    .fi-breadcrumbs-item {
        min-width: 0;
    }

    .fi-breadcrumbs-item-label {
        overflow-wrap: anywhere;
    }

    /* The top bar contains the mobile sidebar toggle. */
    @media (min-width: 1024px) {
        .fi-topbar {
            display: none;
        }
    }
</style>
