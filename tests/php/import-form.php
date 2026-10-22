<?php
define('ABSPATH', __DIR__);
$hooks = [];
function add_action($hook, $callback, ...$unused) { global $hooks; $hooks[$hook] = $callback; }
function is_email($value) { return filter_var($value, FILTER_VALIDATE_EMAIL); }
function sanitize_text_field($value) { return trim(strip_tags($value)); }
function sanitize_textarea_field($value) { return trim(strip_tags($value)); }
function wp_date($format) { return '2026'; }
require __DIR__ . '/../../inc/integrations/import-form.php';
$count=0;
function check($test, $message) { global $count; $count++; if (!$test) { throw new RuntimeException($message); } }
$base=['first_name'=>'Teszt','last_name'=>'Elek','email'=>'demo@example.com'];
check(herizon_cars_import_errors($base,2026)===[], 'All optional vehicle criteria may be empty');
check(herizon_cars_import_errors(['first_name'=>'Ősz','last_name'=>'Tűz','phone'=>'+36 30 123 4567'],2026)===[], 'Phone-only and Hungarian names');
check(herizon_cars_import_errors($base+['phone'=>'+49 (30) 123-4567'],2026)===[], 'International phone');
foreach (['first_name','last_name','email'] as $id) { $v=$base; $v[$id]=''; check(isset(herizon_cars_import_errors($v,2026)[$id]), 'Missing '.$id); }
check(isset(herizon_cars_import_errors(array_replace($base,['first_name'=>'<b></b>']),2026)['first_name']), 'Validate after name sanitization');
foreach (['bad','demo@','x@@example.com'] as $email) { check(isset(herizon_cars_import_errors(array_replace($base,['email'=>$email]),2026)['email']), 'Malformed email'); }
foreach (['123','+36letters1234567','1234567890123456'] as $phone) { check(isset(herizon_cars_import_errors($base+['phone'=>$phone],2026)['phone']), 'Invalid phone'); }
foreach (['year_from'=>1899,'year_to'=>2027,'budget'=>-1,'mileage'=>'1e3','power'=>'1.5'] as $id=>$value) { check(isset(herizon_cars_import_errors($base+[$id=>$value],2026)[$id]), 'Numeric boundary '.$id); }
check(isset(herizon_cars_import_errors($base+['year_from'=>2020,'year_to'=>2010],2026)['year_to']), 'Reversed year range');
check(herizon_cars_import_errors($base+['year_from'=>1900,'year_to'=>2026,'budget'=>0,'mileage'=>0,'power'=>0],2026)===[], 'Zero and year boundary accepted');
check(isset(herizon_cars_import_errors($base+['model'=>['invalid']],2026)['model']), 'Array injection');
check(isset(herizon_cars_import_errors($base+['message'=>str_repeat('x',5001)],2026)['message']), 'Oversized message');
class ImportRecord {
    public $fields;
    public function __construct(public string $id='hc_import_request') { $this->fields=['first_name'=>['raw_value'=>'Teszt','value'=>'Teszt'],'last_name'=>['raw_value'=>'Elek','value'=>'Elek'],'email'=>['raw_value'=>'demo@example.com','value'=>'demo@example.com'],'message'=>['raw_value'=>'<script>test</script>','value'=>'<script>test</script>']]; }
    public function get_form_settings($key) { return $key==='form_id'?$this->id:''; }
    public function get($key) { return $key==='fields'?$this->fields:null; }
    public function update_field($id,$key,$value) { $this->fields[$id][$key]=$value; }
}
class ImportHandler {
    public $errors=[]; public $messages=[];
    public function add_error($id,$message) { $this->errors[$id]=$message; }
    public function add_error_message($message) { $this->messages[]=$message; }
}
$r=new ImportRecord; $h=new ImportHandler; $hooks['elementor_pro/forms/validation']($r,$h);
check(isset($h->errors['demo_notice']), 'Valid synthetic input is still blocked before actions');
check(str_contains($h->messages[0],'nem lett elmentve'), 'Demo rejection is explicit');
check($r->fields['message']['value']==='test', 'Submitted text sanitized');
$h=new ImportHandler; $hooks['elementor_pro/forms/validation'](new ImportRecord('unrelated'),$h);
check($h->errors===[], 'Unrelated forms untouched');
echo "Import form: {$count} assertions PASS\n";
