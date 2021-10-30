/*
  npm i is-template-object;
  node index.js;
*/


isTemplateObject = require('is-template-object');

function template(html, ...values) {
  console.log(isTemplateObject(html), html, values);
}


//--------------------------------------------------


var username = "<script>alert()</script>"; // From evil user


template`<p>Hi ${username}<p>`;

template([`<p>Hi ${username}<p>`]); // Wrong

template(['<p>Hi ', username, '<p>']); // Wrong

