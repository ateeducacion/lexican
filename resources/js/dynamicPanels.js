let addClassTimer = function addClassTimer(element, cssClass, timer){    
    window.getComputedStyle(element[0]).getPropertyValue("opacity")
    window.getComputedStyle(element[0]).getPropertyValue("visibility")
    element.css('z-index', 100)    
    element.addClass([...cssClass])               
    setTimeout(()=>{
        element.removeClass([...cssClass])        
        setTimeout(()=>{
            element.css('z-index', -1)
        },parseFloat(window.getComputedStyle(element[0])['transitionDuration'])*1000)
    },timer)   
};


module.exports.addClassTimer = addClassTimer;