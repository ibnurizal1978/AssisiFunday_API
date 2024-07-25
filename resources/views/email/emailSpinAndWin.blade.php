<link rel="stylesheet" href="{{ asset('/css/edm.css') }}" />

<table border="0" cellpadding=0 cellspacing=0 width=70% style="background: #fff">
  <tr><td colspan="3"><img src="{{ asset('/images/edm/2022-header.png') }}" /></td></tr>
  <tr>
    <td width=20%>&nbsp;</td>
    <td width=60% style="color:#6C7E5C">
      <img src="{{ asset('/public/images/edm/2022-icon-spinandwin.png') }}" />
      <h2>Hi {{ $data['full_name'] }},</h2><br/>
      Congratulations for winning in the Spot, Spin & Win Game at Assisi Fun Day!<br/>
      You have won <font style="color: #F9951D;">{{ $data['prize'] }}</font>.
      <hr/>
      <b>You may redeem your prize at:</b><br/>
      Address: <font style="color: #2FD4E1;">Assisi Hospice, 832 Thomson Road, Singapore 574627</font><br/>
      Collection Dates: <font style="color: #2FD4E1;">10 Oct to 21 Oct 2022</font><br>
      Collection Hours: <font style="color: #2FD4E1;">Monday to Friday, 10am - 8pm</font><br>
      <hr/>
      <br/>
      <b>Terms and Conditions:</b><br>
      <ul>
        <li>This email must be presented during prize collection.</li>
        <li>Prize collection is strictly during the collection hours stated above.</li>
        <li>Any uncollected prize will be forfeited after the stated date of collection.</li>
        <li>This email has no cash value and cannot be exchanged or sold for cash or other items.</li>
      </ul>
      <br/>
        Please contact 9837 4060 between 9am - 5pm from Monday to Friday for enquiries.
      <br/><br/><br/>
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
