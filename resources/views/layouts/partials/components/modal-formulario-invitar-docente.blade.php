<div class="modal fade" id="docentesModal" tabindex="-1" role="dialog" aria-labelledby="docentesLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        @if(isset($diccionarioAula))
        <div class="modal-content">

            <form id="invitarDocenteEmail" class="my-form" method="POST" action="{{ route('aula.invitar', ['diccionario_id'=> $diccionarioAula->id]) }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="codigo_dic" value={{ $diccionarioAula->codigo }}>

                <div class="modal-header">
                    <h5 class="modal-title" id="docentesLabel">{{__('Invitar docentes')}}<nav></nav></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row mb-2">
                        <label for="emailDocente">Email</label>   
                        <input class="form-control" type="text" id="emailDocente" name="emailDocente">
                    </div>
                    <div class="row mb-2">
                        <label for="nombreDocente">Nombre</label>
                        <input class="form-control" type="nombreDocente" id="nombreDocente" name="nombreDocente">
                    </div>
                    <div class="row mb-1">
                        <label for="apellidoDocente">Apellidos</label>
                        <input class="form-control" type="apellidoDocente" id="apellidoDocente" name="apellidoDocente">
                    </div>                          
                    <span class="blockquote-footer text-center mt-0 font-italic">{{__('diccionario.nota_invitar_docente')}}</span>
                </div>
            </form>

            <div class="modal-footer">
                <button type="button" class="btn btn-primary text-nowrap" id="btnInvitarDocenteEmail" >
                    {{ __('Invitar y enviar código') }}
                </button>
                <button type="button" class="btn btn-danger" data-dismiss="modal">@lang('Cancelar')</button> 
            </div>

        </div>
        @endif
    </div>
</div>