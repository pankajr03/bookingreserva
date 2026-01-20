// External Dependencies
import React, {Component, useEffect, useRef} from 'react';

// Internal Dependencies
import './style.css';
const $ = window.jQuery;

class ForgotPasswordSaaS extends Component {

  static slug = 'booknetic_saas_forgot_password';

  render(props) {

    return (
        <Bpanel {...this.props} />
    );
  }

}

function Bpanel(props){

  const ref = useRef();

  const fetchView = async (shortcode)=>{
    let data = new FormData();
    data.append('shortcode',shortcode)

    let bookneticSaaSDiviOptions = JSON.parse( decodeURIComponent( props.bookneticSaaSDivi ) );

    let req = await fetch(bookneticSaaSDiviOptions.url + '/?bkntc_saas_preview=1',{
      method:'POST',
      body:data
    });
    let res = await req.text();
    $(ref.current).html(res)
  }

  useEffect(()=>{
    fetchView('[booknetic-saas-forgot-password]')
  },[])

  return (
      <div style={{pointerEvents:"none"}} ref={ref}>
        Loading...
      </div>
  );
}


export default ForgotPasswordSaaS;
