'use strict';
import Twig from 'twig';

export default class TimeSheetForm
{
    constructor(formObject, resultContainerObject, errorContainerObject){
        this.formObject = formObject;
        this.resultContainerObject = resultContainerObject;
        this.errorContainerObject = errorContainerObject;

        $(document).ready($.proxy(function() {
            this.init();
        }, this));
    }

    //инициализацияЦ
    init (){
        $(this.formObject).on('submit', $.proxy(function(){
            this.sendFormDataAndRenderResponse();
            return false;
        }, this));
    }

    //Отправка запросаи обработка результата
    sendFormDataAndRenderResponse(){
        let _this = this;
        this.clearResult();
        this.clearErrorResult();

        let requestPromise = this.sendFormRequest();

        requestPromise.then(function(data){
            _this.renderResult(data);
        }).catch(function(err){
            //Вывод ошибки
            _this.renderErrorResultBlock(err);
        });
    }

    //отправка запроса
    sendFormRequest()
    {
        let _this = this;

        return new Promise(function(resolve, reject) {
            $.ajax({
                url: $(_this.formObject).attr('action'),
                method: "POST",
                data: $(_this.formObject).serialize(),
                dataType:'json',
                cache: true,
                success: function(data){
                    resolve(data);
                },
                error: function (jqXHR, exception) {
                    reject(jqXHR);
                }
            });
        });
    }


    clearResult(){
        this.resultContainerObject.html('');
    }

    renderResult(data){
        let resultTemplate = Twig.twig({
            href: "/src/views/time_sheet_result.twig",
            async: false
        });

        let resultHtml = resultTemplate.render({ items: data });
        this.resultContainerObject.html(resultHtml);
        $(this.resultContainerObject).find('[data-toggle="tooltip"]').tooltip()
    }


    clearErrorResult(){
        this.errorContainerObject.html('');
        this.errorContainerObject.css('display', 'none');
    }

    renderErrorResultBlock(error){
        this.errorContainerObject.html(error);
        this.errorContainerObject.css('display', 'block');
    }
}