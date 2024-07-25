<link rel="stylesheet" href="{{ asset('/css/edm.css') }}" />
<table border="0" cellpadding=0 cellspacing=0 width=70% style="background: #fff">
  <tr><td colspan="3"><img src="{{ asset('/images/edm/2022-header.png') }}" /></td></tr>
  <tr>
    <td width=20%>&nbsp;</td>
    <td width=60% style="color:#6C7E5C">
    <img src="{{ asset('/public/images/edm/2022-icon-accountverify.png') }}" />
    <h2>Hi {{ $data['full_name'] }},</h2><br/>
    Congratulations on taking your first step to join Assisi Fun Day 2022!<br/>
    You are on the journey to shop and eat for good.
    <br/><br/>
    Please click below to verify your account and start exploring.
    <br/>
    <i>(The link is valid for 30mins only)</i>
    <br/><br/><br/>
    <a href="https://www.assisifunday.sg/account-verification.html?{{ $data['email']}}"><img border=0 src="{{ asset('/images/edm/btn-confirm-acc.png') }}" width="150" /></a>
    <br/><br/><br/>
    <small>If the button does not work, copy and paste the following link into your browser</small><br/><br/>
    <h3>https://www.assisifunday.sg/account-verification.html?{{ $data['email']}}</h3>
    <br/><br/>
    Cheers,
    <br/>Team Assisi
    <br/>
  </td>
  <td width=20%>&nbsp;</td>
  </tr>
    <tr style="height:120px; background:url('{{ asset('/images/edm/2022-footer.png') }}')">
      <td width=10%>&nbsp;</td>
      <td width="80%" align="center" valign="bottom">
        <a href="https://www.assisihospice.org.sg/about-us/">Disclaimer and Intellectual Rights</a> | <a href="https://www.assisihospice.org.sg/about-us/privacy-policy/">Privacy Notice</a> | &copy;2022 Assisi Hospice. All Rights Reserved.
      </td>
      <td width=10%>&nbsp;</td>
    </tr>
</table>
