<div id="template_body"></div>
@push('scripts')
    <script>
        const TemplateBodyCreator = function(){
            
            const baseEl = `<div id="template_body_container">
        <div class="col-sm-12 mt-3 mb-2 d-flex justify-content-between align-items-center">
            <h6 id="template_body_title">
                Body
            </h6>
        </div>
        <div class="rounded-3 border border-4 border-light p-3">
            <div class="row">
                <div class="col-sm-6">
                    <label for="template_body_input" id="template_body_label" class="form-label">Message</label>
                    <textarea class="form-control" rows="10" id="template_body_input" placeholder="{halo} {nama}!"></textarea>
                </div>
                <div class="col-sm-6 d-flex flex-column">
                    <label for="template_body_input_result" id="template_body_label_result" class="form-label">Result</label>
                    <div class="form-control disabled flex-grow-1 d-block" style="white-space: pre-wrap" id="template_body_input_result"></div>
                </div>
            </div>
        </div>
    </div>`;
            
            const getCharString = function(char){
                let len = char.length;
                return `(?<!\\w)([${char}]{${len}})(.+?)\\1(?!\\w)`;
            }
            const creators = {
                ['(\{nama\})']: function(){
                    return "{{\Illuminate\Support\Facades\Auth::user()->display_name}}"
                },
                ['(\{halo\})']: function(){
                    let hours = (new Date()).getHours();
                    if(hours >= 9 && hours < 12){
                        return "Selamat Pagi";
                    }
                    if(hours >= 12 && hours <= 15){
                        return "Selamat Siang";
                    }
                    if(hours > 15 && hours <= 18){
                        return "Selamat Sore"
                    }
                    return "Selamat Malam"
                },
                [getCharString('*')]: function(body){
                    return '<strong>$2</strong>'
                },
                [getCharString('~')]: function(body){
                    return '<del>$2</del>'
                },
                [getCharString('_')]: function(body){
                    return '<i>$2</i>'
                },
                [getCharString('```')]: function(body){
                    return '<pre>$2</pre>'
                },
            }

            const pipe = function(body){
                return Object.keys(creators).reduce((p, c) => {
                    return p.replace(new RegExp(c, 'gi'), creators[c](p));
                }, body);
            }

            const changebody = function(){
                let val = $(this).val();
                $('#template_body_input_result').html(pipe(val));
            }
            
            const init = function(title = "Body"){
                
                if(!document.getElementById('template_body_container')){
                    $('#template_body').append($(baseEl));
                    let input = $('#template_body_input');
                    input.on('keyup', changebody);
                    input.on('change', changebody);
                }

                $('#template_body_title').text(title);

            }
            
            const destroy = function(){
                $('#template_body_container').remove();
            }
            
            const getValue = function(){
                return $('#template_body_input').val();
            }

            const fill = function(value){
                $('#template_body_input')
                    .text(value)
                    .val(value)
                    .trigger('change');
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