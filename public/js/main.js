document.addEventListener('DOMContentLoaded', () => {

    const btnAvatar = document.getElementById('btn-avatar');
    const menuPopout = document.getElementById('menu-popout');

    btnAvatar.addEventListener('click', (evento)=>{
        evento.stopPropagation();
        menuPopout.classList.toggle('oculto');
    });

    document.addEventListener('click',(evento)=>{
        if(!menuPopout.classList.contains('oculto')&&!menuPopout.contains(evento.target)){
            menuPopout.classList.add('oculto');
        }
    });
})