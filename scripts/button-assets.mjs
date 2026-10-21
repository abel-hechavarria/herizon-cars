/** One design contract consumed by PHP, Elementor, Gutenberg and generated CSS. */
import { mkdirSync, readFileSync, writeFileSync, existsSync, readdirSync } from 'node:fs';
import { join } from 'node:path';
const root = join(import.meta.dirname, '..');
const field = (key, label, type, options) => ({ key, label, type, ...(options ? { options } : {}) });
const border = { none:'None', solid:'Solid', dashed:'Dashed', dotted:'Dotted', double:'Double' };
const fields = [
 field('textColor','Text color','color'), field('background','Background','background'),
 field('borderColor','Border color','color'), field('borderWidth','Border width','length'), field('borderStyle','Border style','select',border),
 field('radius','Corner radius','length'),field('shadow','Shadow','shadow'),
 ...['Top','Right','Bottom','Left'].map(side=>field('padding'+side,'Padding '+side.toLowerCase(),'length')),
 field('gap','Text / icon spacing','length'),field('fontFamily','Font family','font'),field('fontSize','Font size','length'),
 field('fontWeight','Font weight','select',{'normal':'Normal','bold':'Bold','100':'100','200':'200','300':'300','400':'400','500':'500','600':'600','700':'700','800':'800','900':'900'}),
 field('lineHeight','Line height','number'),field('letterSpacing','Letter spacing','length'),
 field('textTransform','Text transformation','select',{none:'None',uppercase:'Uppercase',lowercase:'Lowercase',capitalize:'Capitalize'}),
 field('width','Width: auto, 100% or length','length'),field('align','Alignment','select',{'flex-start':'Left',center:'Center','flex-end':'Right'}),
 field('iconColor','Icon color','color'),field('iconBackground','Icon background','background'),
 field('iconSize','Icon size','length'),field('iconDiameter','Icon container diameter','length'),field('iconPadding','Icon padding','length'),
 field('iconBorderColor','Icon border color','color'),field('iconBorderWidth','Icon border width','length'),field('iconBorderStyle','Icon border style','select',border),
 field('iconRadius','Icon corner radius','length'),field('iconShadow','Icon shadow','shadow'),
];
const common = {textColor:'#F5F5F5',background:'transparent',borderColor:'transparent',borderWidth:'0px',borderStyle:'solid',radius:'999px',shadow:'none',paddingTop:'0px',paddingRight:'0px',paddingBottom:'0px',paddingLeft:'0px',gap:'14px',fontFamily:'var(--e-global-typography-accent-font-family, Saira, sans-serif)',fontSize:'12px',fontWeight:'500',lineHeight:'1.3',letterSpacing:'.4px',textTransform:'uppercase',width:'auto',align:'flex-start',iconColor:'#F5F5F5',iconBackground:'transparent',iconSize:'16px',iconDiameter:'48px',iconPadding:'13px',iconBorderColor:'#A5D6F1',iconBorderWidth:'1px',iconBorderStyle:'solid',iconRadius:'50%',iconShadow:'none'};
const presets = {
 pill:{label:'Pill',normal:{...common,textColor:'#101010',background:'#81B3F5',paddingTop:'5px',paddingRight:'20px',paddingBottom:'5px',paddingLeft:'9px',gap:'12px',iconColor:'#81B3F5',iconBackground:'#050505',iconDiameter:'38px',iconPadding:'11px',iconBorderWidth:'0px'},hover:{background:'#A4CAFB',textColor:'#101010',iconColor:'#A4CAFB',iconBackground:'#101010'}},
 outline:{label:'Outlined circle',normal:{...common},hover:{textColor:'#FFFFFF',iconBackground:'#81B3F5',iconColor:'#101010',iconBorderColor:'#81B3F5'}},
 solid:{label:'Filled circle',normal:{...common,iconBackground:'#FFFFFF',iconColor:'#101010',iconDiameter:'42px',iconPadding:'12px',iconBorderWidth:'0px'},hover:{textColor:'#FFFFFF',iconBackground:'#81B3F5',iconColor:'#101010'}},
};
mkdirSync(join(root,'assets'),{recursive:true});
writeFileSync(join(root,'assets/button-schema.json'),JSON.stringify({version:1,fields,presets,breakpoints:{tablet:1024,mobile:767}},null,2)+'\n');
const kebab=s=>s.replace(/[A-Z]/g,m=>'-'+m.toLowerCase());
writeFileSync(join(root,'src/scss/_button-presets.scss'),'// Generated from scripts/button-assets.mjs. Do not edit.\n'+Object.entries(presets).map(([name,preset])=>'.hc-button--'+name+' {\n'+Object.entries(preset.normal).map(([key,value])=>'  --hc-button-preset-'+kebab(key)+': '+value+';').join('\n')+'\n'+Object.entries(preset.hover).map(([key,value])=>'  --hc-button-preset-hover-'+kebab(key)+': '+value+';').join('\n')+'\n}').join('\n'));
const fa = join(root,'node_modules/@fortawesome/fontawesome-free');
if (existsSync(join(fa,'metadata/icon-families.json'))) {
 const meta=JSON.parse(readFileSync(join(fa,'metadata/icon-families.json'),'utf8')), icons={};
 for(const [style,prefix] of [['solid','fas'],['regular','far'],['brands','fab']]) {
  const dir=join(fa,'svgs',style);
  for(const file of readdirSync(dir).filter(f=>f.endsWith('.svg'))) {
   const source=readFileSync(join(dir,file),'utf8'),name=file.slice(0,-4);
   const box=source.match(/viewBox="0 0 (\d+) (\d+)"/),paths=[...source.matchAll(/<path[^>]*\sd="([^"]+)"/g)].map(m=>m[1]);
   if(!box||!paths.length)throw new Error('Invalid icon '+file);
   icons[prefix+':'+name]={label:meta[name]?.label||name,width:Number(box[1]),height:Number(box[2]),paths};
  }
 }
 writeFileSync(join(root,'assets/button-icons.json'),JSON.stringify(icons)+'\n');
 mkdirSync(join(root,'assets/licenses'),{recursive:true});
 writeFileSync(join(root,'assets/licenses/fontawesome-free-LICENSE.txt'),readFileSync(join(fa,'LICENSE.txt'),'utf8'));
 console.log('Generated shared schema and '+Object.keys(icons).length+' local SVG icons.');
} else console.log('Generated shared button schema; install dependencies to generate icons.');
