$(function(){

    $(document).on('click', '.btn-edit', function() {
        
        let id = $(this).val();

        $.ajax({
            type: "GET",
            url: `/participantes/${id}`,
            contentType: 'application/json',
            success: res => {

                res = JSON.parse(res);

                if(!res.error){

                    $('#id').val(res.id);
                    $('#nomeEdit').val(res.nome);
                    $('#qtdConsumoEdit').val(res.consumo);

                } else {

                    toastr.error(res.error,'Erro!');

                }
            }
        });
        
    });

    $("#formEdit").on("submit", event => {

        event.preventDefault();

        const formulario = document.getElementById("formEdit");
        const formData = new FormData(formulario);
        const form = Object.fromEntries(new URLSearchParams(formData).entries());

        $.ajax({
            type: "PUT",
            url: `/editarParticipante/${form.id}`,
            data: JSON.stringify(form),
            contentType: 'application/json',
            success: res => {

                $("#fecharEditar").click();

                if(!res.error){

                    toastr.success(res.message,'Sucesso!');
                    window.location.reload();


                } else {

                    toastr.error(res.error,'Erro!');

                }

            }

        });

    });

    $("#formDelete").on("submit", event => {

        event.preventDefault();

        const id = $('.btn-delete').val();

        $.ajax({
            type: "DELETE",
            url: `/deletarParticipante/${id}`,
            contentType: 'application/json',
            success: res => {

                $("#fecharDeletar").click();

                if(!res.error){

                    toastr.success(res.message,'Sucesso!');
                    window.location.reload();


                } else {

                    toastr.error(res.error,'Erro!');

                }

            }

        });
    
    });

});