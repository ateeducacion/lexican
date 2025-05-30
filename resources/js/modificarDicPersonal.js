/** 
 * modificarDicPersonal.js
 * Javascript para la pagina de modifictar dicccionario personal
 * 
 * @author Fernando Ramirez Perez <fernando.ramirez@altia.es>
 * @version 1.0.0
 * 
*/

import $ from 'jquery';

// core version + navigation, pagination modules:
import Swiper, { Navigation, Pagination } from 'swiper';

// configure Swiper to use modules
Swiper.use([Navigation, Pagination]);

import {rutaEnArray} from './utils';


$( document ).ready(function() {

    // solo se ejecuta en estas rutas ( hay que cambiar el punto por - )
    const rutas = [
        'diccionario-modificar-ver',
        // 'diccionarioaula.index',
        // 'diccionarioaula.create',
        // 'diccionarioaula.save',
        // 'diccionarioaula.entradas',
        // 'diccionarioaula.edit'
    ];
    if ( !rutaEnArray(rutas) ) {
        // console.log('no se carga modificarDicPersonal.js  en ruta: ', $('body')[0].classList.value);
        // console.log('no esta en ', rutas);
        return false;    
    }

// console.log("carga modificarDicPersonal");
var mySwiper = new Swiper('.swiper-container', {
    // Optional parameters
    direction: 'horizontal',
    // loop: true,
    slidesPerView: 5,
    spaceBetween: 40,
    watchSlidesVisibility: true,
    centerInsufficientSlides: true,
    breakpoints: {
        // when window width is >= 320px
        320: {
          slidesPerView: 2,
          spaceBetween: 20
        },
        // when window width is >= 480px
        480: {
          slidesPerView: 2,
          spaceBetween: 30
        },
        // when window width is >= 640px
        576: {
          slidesPerView: 3,
          spaceBetween: 40
        },
        // when window width is >= 640px
        768: {
          slidesPerView: 4,
          spaceBetween: 40
        },
        // when window width is >= 640px
        992: {
          slidesPerView: 5,
          spaceBetween: 40
        },// when window width is >= 640px
        1200: {
            slidesPerView: 6,
            spaceBetween: 40
          }
      },
    
    // If we need pagination
    pagination: {
        el: '.swiper-pagination',
        clickable: true,
    },

    // // Navigation arrows
    navigation: {
      nextEl: '.swiper-button-next',
      prevEl: '.swiper-button-prev',
    //   hide: true,
    },

    // And if we need scrollbar
    // scrollbar: {
    //   el: '.swiper-scrollbar',
    //   hide: true,
    // },

    // Resuleve probelma de los botones de navegacion que no funcionan al redimensionar
    // observer: true, 
    // observeParents: true
  });

  
  // $('.swiper-slide').on('click', 'input[name=avatar_nuevo]', function(ev){
    
    // console.log('click slider sel-avatar');
  // if ($('#selectedSliderEstudiante').val() != $(ev.delegateTarget).data('id')) {
  //     $('#selectedSliderEstudiante').val($(ev.delegateTarget).data('id'))                
  //     $('#estudiante').val($(ev.delegateTarget).data('id'))                
  // } else { 
  //     $('#selectedSliderEstudiante').val('')
  //     $(ev.currentTarget).prop('checked', false)
  //     $('#estudiante').val(0)
  // }
// });

  

});