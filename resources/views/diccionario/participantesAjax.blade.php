@if(isset($errormsg))
    <tr class="table-danger">
        <td colspan="3" style="width:100%">{{  __('diccionario.'.$errormsg) }}</td>
    </tr>
@endif

@if(isset($diccionarioAula))
    @foreach( $participantes as $partcipante)
<tr>
    <td>{{$partcipante->persona()->first()->nombre}}</td>
    <td>{{$partcipante->persona()->first()->apellidos}}</td>
    <td>
    @if($partcipante->estado == config('ctes.estados.activo'))
        <img data-toggle="tooltip" data-id="{{$partcipante->id}}"

        {{-- @can('isOwner', $diccionarioAula ) --}}
            class="cursor-pointer"
            data-action="deshabilitarParticipante"
            title="{{ __('diccionario.deshabilitar acceso al diccionario') }}"
        {{-- @endcan --}}
        data-placement="top"
        {{-- @cannot('isOwner', $diccionarioAula )
            title="{{ __('diccionario.usuario habilitado') }}"
        @endcannot --}}

        {{-- id="deshabilitarParticipante"  --}}
        name="deshabilitarParticipante"  src="{{  asset('imagenes/ico-ojo.svg')  }}" alt="deshabilitar">
    @else
        <img data-toggle="tooltip" data-id="{{$partcipante->id}}"
        {{-- @can('isOwner', $diccionarioAula ) --}}
            data-action="habilitarParticipante"
            class="cursor-pointer"
            title="{{ __('diccionario.habilitar acceso al diccionario') }}"
        {{-- @endcan --}}
        data-placement="top"
        {{-- @cannot('isOwner', $diccionarioAula )
            title="{{ __('diccionario.usuario deshabilitado') }}"
        @endcannot --}}
        {{-- id="habilitarParticipante"  --}}
        name="habilitarParticipante"  style="width: 36px;" src="{{  asset('imagenes/ico-ojo-desactivado.svg')  }}" alt="habilitar">
    @endif
    </td>
    @if($profesorado === true)
        <td>
        @if($partcipante->rol_diccionario_id == config('ctes.rol.docente'))
            <img data-toggle="tooltip" data-id="{{$partcipante->id}}"

            {{-- @can('isOwner', $diccionarioAula ) --}}
                class="cursor-pointer"
                data-action="deshabilitarParticipanteAdmin"
                title="{{ __('diccionario.deshabilitar permisos de edicion') }}"
            {{-- @endcan --}}
            data-placement="top"
            {{-- @cannot('isOwner', $diccionarioAula )
                title="{{ __('diccionario.usuario sin permisos edicion') }}"
            @endcannot --}}

            {{-- id="deshabilitarParticipanteAdmin"  --}}
            name="deshabilitarParticipanteAdmin"  src="{{  asset('imagenes/ico-ojo.svg')  }}" alt="deshabilitar">
        @else
            <img data-toggle="tooltip" data-id="{{$partcipante->id}}"
            {{-- @can('isOwner', $diccionarioAula ) --}}
                data-action="habilitarParticipanteAdmin"
                class="cursor-pointer"
                title="{{ __('diccionario.habilitar permisos de edicion') }}"
            {{-- @endcan --}}
            data-placement="top"
            {{-- @cannot('isOwner', $diccionarioAula )
                title="{{ __('diccionario.usuario sin permisos edicion') }}"
            @endcannot --}}
            {{-- id="habilitarParticipanteAdmin"  --}}
            name="habilitarParticipanteAdmin"  style="width: 36px;" src="{{  asset('imagenes/ico-ojo-desactivado.svg')  }}" alt="habilitar">
        @endif
        </td>
    @endif
</tr>
    @endforeach
@endif
