@extends('diccionario/home')

@section('title', __('diccionario.exportarpdf__titulo') )

@section('content')
@parent

<h3>{{ __('diccionario.exportarpdf__titulo') }}</h3>

<div class="dc-panel-gris card">
    @include('layouts.partials.dpOptionsPDFform')

    
</div>

@endsection

@section('scripts')
<script>
$(function() {
    const hideShowTematicas = function() {
        if ($('#exportOnlyTagged').is(':checked')) {
            $('#tematicas').removeClass('d-none');
        } else {
            $('#tematicas').addClass('d-none');
        }
    }
    // lo comprobamos al cargar la pagina 
    hideShowTematicas();
    // y cuando cambiamos el checkbox
    $("#exportOnlyTagged").change( hideShowTematicas );
});

// TODO: copiado de acepcionEdit.blade.php esta tambien en dpAcetpiconFormulario.blade.php abria que pasarlo a un archivo js
function tematicaAnadir() {
            // Añado la temática a la lista de seleccionadas
            $("#tematicas_disponibles > option").filter(":selected").each(function() {
                $('#tematicas_seleccionadas').append($('<option>', {
                    value: this.value,
                    text: this.text
                }));
                // Borro la temática de la lista de disponibles
                this.remove();
            });

            // Reordenar las listas de temáticas
            sortSelect(document.getElementById('tematicas_disponibles'));
            sortSelect(document.getElementById('tematicas_seleccionadas'));

            // Recalculo la lista de tematicas selecciondas
            calcularTematicasSeleccionadas();
        }

        function tematicaQuitar() {
            // Añado la temática a la lista de disponibles
            $("#tematicas_seleccionadas > option").filter(":selected").each(function() {
                $('#tematicas_disponibles').append($('<option>', {
                    value: this.value,
                    text: this.text
                }));
                // Borro la temática de la lista de seleccionadas
                this.remove();
            });

            // Reordenar las listas de temáticas
            sortSelect(document.getElementById('tematicas_disponibles'));
            sortSelect(document.getElementById('tematicas_seleccionadas'));

            // Recalculo la lista de tematicas selecciondas
            calcularTematicasSeleccionadas();
        }

        function sortSelect(selElem) {
            var tmpAry = new Array();
            for (var i = 0; i < selElem.options.length; i++) {
                tmpAry[i] = new Array();
                tmpAry[i][0] = selElem.options[i].text;
                tmpAry[i][1] = selElem.options[i].value;
            }
            tmpAry.sort();
            while (selElem.options.length > 0) {
                selElem.options[0] = null;
            }
            for (var i = 0; i < tmpAry.length; i++) {
                var op = new Option(tmpAry[i][0], tmpAry[i][1]);
                selElem.options[i] = op;
            }
            return;
        }

        function calcularTematicasSeleccionadas() {
            // console.log('lista tematicas', $("#listaTematicas").val() );
            $("#listaTematicas").val("");
            // console.log('lista tematicas borrada: ', $("#listaTematicas").val() );
            $("#tematicas_seleccionadas > option").each(function() {
                var listaTematicas = $("#listaTematicas").val();
                listaTematicas = listaTematicas + "," + this.value;
                $("#listaTematicas").val(listaTematicas);
                // console.log( 'lista tematicas opcion:', this.name, ':',  $("#listaTematicas").val() );
            });
        }

        // Filtrar las temáticas disponibles https://stackoverflow.com/questions/1447728/how-to-dynamic-filter-options-of-select-with-jquery
        jQuery.fn.filterByText = function(textbox, select_comparado) {
            return this.each(function() {
                var listaTematicas = [];
                var select = this;
                var options = [];
                var seleccionadas = [];


                // Rellenamos listaTematicas[] con las opciones de tematicas_disponibles y de tematicas_seleccionadas
                $(select).find('option').each(function() {
                    listaTematicas.push({
                        value: $(this).val(),
                        text: $(this).text()
                    });
                });
                $(select_comparado).find('option').each(function() {
                    listaTematicas.push({
                        value: $(this).val(),
                        text: $(this).text()
                    });
                });



                // Rellenamos options[] con las opciones de tematicas_disponibles
                $(select).find('option').each(function() {
                    options.push({
                        value: $(this).val(),
                        text: $(this).text()
                    });
                });
                $(select).data('options', options);

                $(textbox).bind('change keyup', function() {

                    // Refrescamos options[] con las todas las opciones
                    options = [];
                    options = listaTematicas.slice();

                    // Rellenamos seleccionadas[] con los valores de tematicas_seleccionadas
                    seleccionadas = [];
                    $(select_comparado).find('option').each(function() {
                        seleccionadas.push($(this).val());
                    });

                    // Recargo el tematicas_disponibles con todas las opciones posibles
                    $(select).empty().data('options');
                    var search = $.trim($(this).val());
                    var regex = new RegExp(search, "gi");

                    $.each(options, function(i) {
                        var option = options[i];
                        if (option.text.match(regex) !== null) {
                            if ($.inArray(option.value, seleccionadas) == -1) {
                                $(select).append(
                                    $('<option>').text(option.text).val(option.value)
                                );
                            } else {
                                // la opción está en el select tematicas_seleccionadas
                            }
                        }
                    });
                });
            });
        };

        jQuery(function($) {
            $('#tematicas_disponibles').filterByText($('#tematica_buscador'), $('#tematicas_seleccionadas'));
            
            // Si no se ejectuta al principio se pierden las tematicas guardadas:
            calcularTematicasSeleccionadas();
            if ($('#idioma_palabra').val() != ''){
                $('#otroLenguaje').show();
            }
        });
</script>
@endsection