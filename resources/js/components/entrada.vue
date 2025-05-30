<template>
<div>
    <h2>Entrada</h2>
    <div class="row">
        <div class="col-md-8">
            <h5><span class="card-title">{{title}}</span>
                @if(isset($entrada->id))
                    <a :href="link_edit" class="icon">
                        <img :src="image_edit" alt="Editar">
                    </a>
                    <a :href="link_delete" v-on:click="showConfirmDialog" class="icon">
                        <img :src="image_delete" alt="Borrar">
                    </a>
                @endif
            </h5>
        </div>


        <div class="col-md-4 text-right" v-if="entrada_id !== null">
                <a :href="link_create_aception" class="add-acepcion">
                    {{ txt_acepcion_anadir }}
                        <img :src="image_create_aception" alt="IMAGEN">
                </a>
        </div>

    </div>
</div>
</template>

<script>
export default {
    name: 'entrada',
    props: {
        image: String, 
        title: String,
        text: String,
        entrada_id: Number,
        link_edit: String,
        link_delete: String,
        link_create_aception: String,
        image_edit: String,
        image_delete: String,
        image_create_aception: String,
        txt_acepcion_anadir: String,

    },
    methods: {
        showConfrimDialog: ()=>{
            Console.log('picado en confirmar dialog')
        }
    },
    data: () => {
        expanded: false
    }
}
</script>

<style>
    .card-title {
        font-size: 20px;
        line-height: 24px;        
        border-bottom: 1px solid;
        margin-right: 1.5rem;
    }
    .card-title a.icon {
            display: inline-block;
            position: relative;
            width: 19px;
            height: 19px;
    }
    .card-title a.icon img{
            position: absolute;
            width: 100%;
            height: 100%;
        }
    .add-acepcion {
        font-size: 14px;
        line-height: 17px;
        position: absolute;
        bottom: 0;
        width: 100%;
        left: 0;
    }
</style>
