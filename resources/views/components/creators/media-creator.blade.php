@push('styles')
    <style>
        .image-input {
            flex-grow: 1;
            background-color: transparent;
            border: none;
            padding: 8px 12px;
            border-radius: 8px;
        }
    </style>
@endpush

<div id="template_image">

</div>

@push('scripts')
    <script src="{{url('/vendor/laravel-filemanager/js/stand-alone-button.js')}}"></script>
    <script>

        const TemplateMediaCreator = function(){

            let baseEl = `<div id="template_image_container">
                            <label class="form-label">Image</label>
                                <div class="input-group bg-light rounded d-flex align-items-center p-2 w-100">
                                     <div class="input-group-btn">
                                       <a id="template_image_path" data-input="thumbnail" data-preview="holder" class="btn btn-primary text-white">
                                         <i class="material-icons">image</i> Choose
                                       </a>
                                     </div>
                                    <input id="thumbnail" class="image-input" type="text" name="image" disabled>
                                </div>
                            </div>`;


            const init = function(){
                if(!document.querySelector('#template_image_container')){
                    $('#template_image').append($(baseEl));
                    $('#template_image_path').filemanager('file')
                }
            }
            const getValue = function(){
                return $('#thumbnail').val() ?? null;
            }
            const destroy = function(){
                $('#template_image_container').remove();
            }
            const fill = function(value){
                $('#template_image_path').val(value);
                $('#thumbnail').val(value);
            }

            return {
                init,
                destroy,
                getValue,
                fill
            }
        }

    </script>
@endpush