(()=>{"use strict";const e=window.React,t=(window.wp.i18n,window.wc.wcBlocksRegistry),n=window.wp.htmlEntities,i=(0,window.wc.wcSettings.getSetting)("paytabs_blocks_data",{}),s=t=>{const{PaymentMethodLabel:n}=t.components;return(0,e.createElement)(e.Fragment,null,t.setting.description&&(0,e.createElement)("div",{style:{display:"flex",justifyContent:"space-between",width:"100%",paddingRight:5,paddingLeft:5}},(0,e.createElement)(n,{text:t.setting.description})))},a=t=>{const{PaymentMethodLabel:i}=t.components;return(0,e.createElement)("div",{style:{display:"flex",justifyContent:"space-between",width:"100%",paddingRight:5,paddingLeft:5}},(0,e.createElement)(i,{text:(0,n.decodeEntities)(t.setting.title)}),""!=t.setting.icon&&(0,e.createElement)("img",{src:t.setting.icon,alt:t.setting.name}))};i.blocks.forEach((i=>{let o=i.supports.includes("tokenization")&&i.enable_tokenise,c={name:i.name,label:(0,e.createElement)(a,{setting:i}),content:(0,e.createElement)(s,{setting:i}),edit:(0,e.createElement)("div",null,(0,n.decodeEntities)(i.description)),canMakePayment:()=>!0,ariaLabel:(0,n.decodeEntities)(i.title),supports:{showSavedCards:o,showSaveOption:o,features:i.supports}};(0,t.registerPaymentMethod)(c)}))})();