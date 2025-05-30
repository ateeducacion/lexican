<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'Debe aceptar el campo :attribute.',
    'active_url' => 'El campo :attribute no es una URL válida.',
    'after' => 'El campo :attribute debe ser una fehca posterior a :date.',
    'after_or_equal' => 'El campo :attribute debe ser una fecha igual o posterior a :date.',
    'alpha' => 'El campo :attribute sólo debe contener letras.',
    'alpha_dash' => 'El campo :attribute sólo puede contener letras, números, guiones y guiones bajos.',
    'alpha_num' => 'El campo :attribute sólo puede contener letras y números.',
    'array' => 'El campo :attribute debe ser un array.',
    'before' => 'El campo :attribute debe ser una fecha anterior a :date.',
    'before_or_equal' => 'El campo :attribute debe ser una fecha igual o anterior a :date.',
    'between' => [
        'numeric' => 'El campo :attribute debe estar entre :min y :max.',
        'file' => 'El campo :attribute debe estar entre :min y :max kilobytes.',
        'string' => 'El campo :attribute debe estar entre :min y :max caracteres.',
        'array' => 'El campo :attribute debe tener entre :min y :max items.',
    ],
    'boolean' => 'El campo :attribute debe ser verdadero o falso.',
    'confirmed' => 'El campo :attribute y su confirmación no coindicen.',
    'date' => 'El campo :attribute no es una fecha dálida.',
    'date_equals' => 'El campo :attribute debe ser la misma fecha que :date.',
    'date_format' => 'El campo :attribute no cumple el formato :format.',
    'different' => 'Los campos :attribute y :other deben ser diferentes.',
    'digits' => 'El campo :attribute debe tener :digits dígitos.',
    'digits_between' => 'El campo :attribute debe estar entre :min y :max digits.',
    'dimensions' => 'El campo :attribute tiene dimensiones de imagen incorrectas.',
    'distinct' => 'El campo :attribute tiene un valor duplicado.',
    'email' => 'El campo :attribute debe ser una dirección de correo correcta.',
    'ends_with' => 'El campo :attribute debe acabar con uno de los siguientes: :values',
    'exists' => 'El campo :attribute seleccionado no es correcto.',
    'file' => 'El campo :attribute debe ser un fichero.',
    'filled' => 'El campo :attribute debe tener un valor.',
    'gt' => [
        'numeric' => 'El campo :attribute debe ser mayor que :value.',
        'file' => 'El campo :attribute debe ser mayor que :value kilobytes.',
        'string' => 'El campo :attribute debe ser mayor que :value caracteres.',
        'array' => 'El campo :attribute debe tener más de :value items.',
    ],
    'gte' => [
        'numeric' => 'El campo :attribute debe ser igual o mayor que :value.',
        'file' => 'El campo :attribute debe ser igual o mayor que :value kilobytes.',
        'string' => 'El campo :attribute debe ser igual o mayor que :value caracteres.',
        'array' => 'El campo :attribute debe tener al menos :value items.',
    ],
    'image' => 'El campo :attribute debe ser una imagen.',
    'in' => 'El campo :attribute seleccionado no es correcto.',
    'in_array' => 'El campo :attribute no existe en :other.',
    'integer' => 'El campo :attribute debe ser un entero.',
    'ip' => 'El campo :attribute debe tener una dirección IP.',
    'ipv4' => 'El campo :attribute debe tener una dirección IPv4.',
    'ipv6' => 'El campo :attribute debe tener una dirección IPv6.',
    'json' => 'El campo :attribute debe tener una cadena JSON.',
    'lt' => [
        'numeric' => 'El campo :attribute debe ser menor que :value.',
        'file' => 'El campo :attribute debe ser menor que :value kilobytes.',
        'string' => 'El campo :attribute debe ser menor que :value caracteres.',
        'array' => 'El campo :attribute debe tener menos de :value items.',
    ],
    'lte' => [
        'numeric' => 'El campo :attribute debe ser igual o menor que :value.',
        'file' => 'El campo :attribute debe ser igual o menor que :value kilobytes.',
        'string' => 'El campo :attribute debe ser igual o menor que :value caracteres.',
        'array' => 'El campo :attribute no debe tener más de :value items.',
    ],
    'max' => [
        'numeric' => 'El campo :attribute no puede ser más grande de :max.',
        'file' => 'El campo :attribute no puede ser más grande de :max kilobytes.',
        'string' => 'El campo :attribute no puede ser más grande de :max characteres.',
        'array' => 'El campo :attribute no debe tener más de :max items.',
    ],
    'mimes' => 'El campo :attribute debe ser un fichero de tipo: :values.',
    'mimetypes' => 'El campo :attribute debe ser un fichero de tipo: :values.',
    'min' => [
        'numeric' => 'El campo :attribute debe tener al menos :min.',
        'file' => 'El campo :attribute debe tener al menos :min kilobytes.',
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
        'array' => 'El campo :attribute debe tener al menos :min items.',
    ],
    'not_in' => 'El campo :attribute seleccionado no es correcto.',
    'not_regex' => 'El formato del campo :attribute no es correcto.',
    'numeric' => 'El campo :attribute debe ser un número.',
    'present' => 'El campo :attribute debe estar presente.',
    'regex' => 'El campo :attribute format no es correcto.',
    'required' => 'El campo :attribute es obligatorio.',
    'required_if' => 'El campo :attribute es obligatorio si :other es :value.',
    'required_unless' => 'El campo :attribute es obligatorio unless :other es uno de :values.',
    'required_with' => 'El campo :attribute es obligatorio si :values está presente.',
    'required_with_all' => 'El campo :attribute es obligatorio si :values están presentes.',
    'required_without' => 'El campo :attribute es obligatorio si :values no está presente.',
    'required_without_all' => 'El campo :attribute es obligatorio si ninguno de :values están presentes.',
    'same' => 'Los campos :attribute y :other deben coincidir.',
    'size' => [
        'numeric' => 'El campo :attribute debe tener :size.',
        'file' => 'El campo :attribute debe tener :size kilobytes.',
        'string' => 'El campo :attribute debe tener :size caracteres.',
        'array' => 'El campo :attribute debe contener :size items.',
    ],
    'starts_with' => 'El campo :attribute debe comenzar con uno de los siguientes: :values',
    'string' => 'El campo :attribute debe ser una cadena.',
    'timezone' => 'El campo :attribute debe ser una zona válida.',
    'unique' => 'El campo :attribute ya ha sido seleccionado.',
    'uploaded' => 'El campo :attribute no se ha podido subir al servidor.',
    'url' => 'El formato del campo :attribute no es correcto.',
    'uuid' => 'El campo :attribute debe ser un UUID válido.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'entrada_entrada' => [
            'required' => 'Debes especificar un texto para la entrada',
        ],
        'pautasEspecificas' => [
            'required_if' => 'No se ha escrito ninguna pauta específica.',
        ],
        'comentariosVisibleFecha' => [
            'required_if'       => 'El campo Fecha para los comentarios es obligatorio si visibilidad comentarios es Visibles los anteriores a:',
            'before_or_equal'   => 'El campo Fecha para los comentarios debe ser una fecha igual o anterior a la de hoy.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [],

];
