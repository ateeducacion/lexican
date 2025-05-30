{{customLoggin(
    config('ctes.log_levels.info'),
    config('ctes.log_types.routeInfo'),
    ['file' => __FILE__, 'line' => __LINE__],
    url()->current(),
    null
    )
}}   