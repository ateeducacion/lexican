<div class="row">
    <div class="col-md-12">
        <h5 class="card-title">Editar nombre de entrada en tu diccionario</h5>
    </div>
</div>
<div class="dc-panel-gris card" style="width: 80%;">

    
    <div class="card-body">
        
        <!-- <p class="card-text">With supporting text below as a natural lead-in to additional content.</p> -->

        <form class="my-form" method="POST" action="{{ route('entrada.update', [$datos->entrada->dic_personal_id, 'entrada_id' => $datos->entrada->id]) }}">
            

            @csrf

            <input type="hidden" name="entrada_id" value="{{ $datos->entrada->id }}" />

            <div class="form-row">

                
                <div class="form-group col-md-12">
                    <input class="form-control" id="entrada_entrada" name="entrada_entrada" maxlength="{{ config('ctes.constantes_entradas.max_entrada') }}" value="{{ $datos->entrada->entrada }}" />
                </div>
            </div>

            <div class="text-center">
                <button type="submit" class="btn btn-primary">GUARDAR</button>
                <a href="{{url()->previous()}}" class="btn btn-danger">CANCELAR</a>
            </div>
        </form>

    </div>
</div>