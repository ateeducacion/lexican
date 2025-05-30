<!-- Modal -->
<div class="modal fade" 
id="comentarModal_{{$entrada->id}}" t
abindex="-1" role="dialog" aria-labelledby="comentarModalLabel" aria-hidden="true">
    <div class="modal-dialog shadow-lg" role="document" style="max-width:750px">
        <div class="modal-content">
            <form style="margin:0;" data-parent="{{$entrada->id}}" data-event="comentar" method="POST" action="{{route('diccionarioaula.comentar', ['id' => $diccionario->id, 'identrada' => $entrada->id])}}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="comentarModalLabel">{{__('Comentar entrada')}}<nav></nav></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        {{$entrada->entrada}} 
                        @if( isset($entrada->dpPersonal) )
                            {{-- {{ dd($entrada) }}
                            {{ dd($entrada->dpPersonal, $entrada->dpPersonal->persona)  }} --}}
                            ( {{$entrada->dpPersonal->persona->nombre}} 
                            {{$entrada->dpPersonal->persona->apellidos}} )
                        @endif
                    </div>
                    <textarea value="@lang('Escriba un nuevo comentario')" style="width: 100%;height: 150px;" class="textarea-tiny" name="tinyComentario" id="tinyComentario{{ $id }}" cols="30" rows="10"></textarea>
                    <div style="font-size: 11pt" class="offset-1 col-10 col-11-sm text-center font-size-10 mt-0 mb-3 font-italic">
                            {{__('La configuración actual de visibilidad de comentarios para el alumnado es ').($entrada->dicAula->comentarios_visibles == config('ctes.comentarios_visibles.visible')? '"visibles"': '"no visibles"')}} 
                    </div>
                    <div>@lang('Historial de comentarios')</div>        
                    <div  class="listado-comentarios">
                        @include('layouts.partials.components.botones.entrada.comentarios', [
                                        'entrada'=> $entrada
                                    ])
                    </div>                                
                </div>
                <div class="modal-footer">
                    <input type="submit" class="btn btn-primary" data-target="#comentarModal_{{$entrada->id}}" value="{{__('Guardar')}}">            
                    <button type="button" data-event="remove-editor" class="btn btn-secondary btn-danger" data-dismiss="modal">{{__('Cerrar')}}</button>            
                </div>
            </form>
        </div>
    </div>
</div>
<!-- End Modal -->



{{-- 
<div class="modal-header">
    <h5 class="modal-title" id="comentarModalLabel">{{__('Comentar entrada')}}<nav></nav></h5>
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
    <span aria-hidden="true">&times;</span>
    </button>
</div> 



<form style="margin:0;width: 100%;" data-parent="{{$entrada->id}}" data-event="comentar" method="POST" action="{{route('diccionarioaula.comentar', ['id' => $diccionario->id, 'identrada' => $entrada->id])}}">
    @csrf
    
    <div class="text-left">
        <div class="mb-2">
            {{$entrada->entrada}} 
            @if( isset($entrada->dpPersonal) )
                ( {{$entrada->dpPersonal->persona->nombre}} 
                {{$entrada->dpPersonal->persona->apellidos}} )
            @endif
        </div>
        <textarea value="@lang('Escriba un nuevo comentario')" style="width: 100%;height: 150px;" class="textarea-tiny" name="tinyComentario" id="tinyComentario" cols="30" rows="10"></textarea>
        <div style="font-size: 11pt" class="offset-1 col-10 col-11-sm text-center font-size-10 mt-0 mb-3 font-italic">
            {{__('La configuración actual de visibilidad de comentarios para el alumnado es ').($entrada->dicAula->comentarios_visibles == config('ctes.comentarios_visibles.visible')? '"visibles"': '"no visibles"')}} 
        </div>
        <div>@lang('Historial de comentarios')</div>        
        <div  class="listado-comentarios">
            @include('layouts.partials.components.botones.entrada.comentarios', [
                'entrada'=> $entrada
            ])
        </div>                                
    </div>

    <input type="submit" class="btn btn-primary" data-target="#comentarModal_{{$entrada->id}}" value="{{__('Guardar')}}">            
<button type="button" data-event="remove-editor" class="btn btn-secondary btn-danger" data-dismiss="modal">{{__('Cerrar')}}</button>            
    
</form>



 --}}
