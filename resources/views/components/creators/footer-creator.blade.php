<div id="template_footer"></div>
@push('scripts')
    <script>
        const TemplateFooterCreator = function(spintaxCreator){

            const placeholder = '{{"\{\{Halo|Selamat Siang\}\}"}} {{"\{\{nama\}\}"}} dengan nomor telepon {{"\{\{nomor\}\}"}}';
            const baseEl = `<div id="template_footer_container">
        <div class="col-sm-12 mt-3 mb-2 d-flex justify-content-between align-items-center">
            <h6>
                Footer (Optional)
            </h6>
        </div>
        <div class="rounded-3 border border-4 border-light p-3">
            <div class="row">
                <div class="col-sm-6">
                    <label for="template_footer_input" id="template_footer_label" class="form-label">Message</label>
                    <textarea class="form-control" rows="10" id="template_footer_input" placeholder="${placeholder}"></textarea>
                </div>
                <div class="col-sm-6 d-flex flex-column">
                    <label for="template_footer_input_result" id="template_footer_label_result" class="form-label">Result</label>
                    <div class="form-control disabled flex-grow-1 d-block" style="white-space: pre-wrap" id="template_footer_input_result"></div>
                </div>
            </div>
            <div class="spintax_items mt-2">
                <div class="spintax_item cursor-pointer" id="footer_spintax_name">Name</div>
                <div class="spintax_item cursor-pointer" id="footer_spintax_number">Number</div>
                <div class="spintax_item cursor-pointer" id="footer_spintax_insert">Spintax</div>
                <div class="spintax_item cursor-pointer" id="footer_spintax_var_1">Variable 1</div>
                <div class="spintax_item cursor-pointer" id="footer_spintax_var_2">Variable 2</div>
                <div class="spintax_item cursor-pointer" id="footer_footer_spintax_var_3">Variable 3</div>
            </div>
        </div>
    </div>`;
            const getCharString = function(char){
                let len = char.length;
                return `(?<!\\w)([${char}]{${len}})(.+?)\\1(?!\\w)`;
            }
            const creators = {
                ['(\{\{nama\}\})']: function(){
                    return "{{\Illuminate\Support\Facades\Auth::user()->display_name}}"
                },
                ['(\{\{nomor\}\})']: function(){
                    return "08xxxxxxxxxx"
                },
                ['(\{\{var([0-9])+\}\})']: function(){
                    return "<small>[Variable($2)]</small>"
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

            const pipe = function(text) {
                let replaced = Object.keys(creators).reduce((p, c) => {
                    return p.replace(new RegExp(c, 'gi'), creators[c](p));
                }, text);

                for(let spintax of spintaxCreator.getData()){
                    let combinedItems = '\{\{' + spintax.items.map(i => i.text).join('|') + '\}\}';
                    let index = replaced.indexOf(combinedItems);
                    while(index >= 0){
                        replaced = replaced.replace(combinedItems, '<small>[Spintax(' + spintax.label + ')]</small>');
                        index = replaced.indexOf(combinedItems);
                    }
                }

                let regexp = "(\{\{(([\\w\\s?|]+)[|]([\\w\\s?|]+))+\}\})";
                let matches = replaced.match(new RegExp(regexp, 'gi')) || [];

                for(let match of matches){
                    let inner = match.replace('\{\{', '').replace('\}\}', '');
                    let splits = inner.split('|');
                    if(splits.some(s => !s.length)){
                        replaced = replaced.replace(match, '<small>[Invalid Spintax]</small>')
                        continue;
                    }
                    replaced = replaced.replace(match, '<small>[Spintax(' + splits.length + ')]</small>')
                }
                replaced = replaced.replace(new RegExp('([\{]{2}|[\}]{2})', 'gi'), '');
                return replaced;
            }

            const changefooter = function(){
                let val = $('#template_footer_input').val();
                $('#template_footer_input_result').html(pipe(val));
            }

            const init = function(){

                if(!document.getElementById('template_footer_container')){
                    $('#template_footer').append($(baseEl));
                    let input = $('#template_footer_input');
                    input.on('keyup', changefooter);
                    input.on('change', changefooter);

                    $('#footer_spintax_name').click(function(){
                        input.val(input.val() + '\{\{nama\}\}')
                        changefooter();
                    });
                    $('#footer_spintax_number').click(function(){
                        input.val(input.val() + '\{\{nomor\}\}')
                        changefooter();
                    });
                    $('#footer_spintax_var_1').click(function(){
                        input.val(input.val() + '\{\{var1\}\}')
                        changefooter();
                    });
                    $('#footer_spintax_var_2').click(function(){
                        input.val(input.val() + '\{\{var2\}\}')
                        changefooter();
                    });
                    $('#footer_spintax_var_3').click(function(){
                        input.val(input.val() + '\{\{var3\}\}')
                        changefooter();
                    });
                    $('#footer_spintax_insert').click(function(){
                        spintaxCreator.setTarget(input, changefooter);
                    });
                }
            }

            const destroy = function(){
                $('#template_footer_container').remove();
            }

            const getValue = function(){
                return $('#template_footer_input').val();
            }

            const fill = function(value){
                $('#template_footer_input')
                    .val(value)
                    .trigger('change');
            }

            return {
                init,
                destroy,
                getValue,
                fill,
            }
        }
    </script>
@endpush