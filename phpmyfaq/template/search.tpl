<h2>{msgSearch}</h2>
	<p>
    <form action="{writeSendAdress}" method="post">
    <fieldset>
    <legend>{msgSearchWord}</legend>
	
    <input class="inputfield" type="text" name="suchbegriff" size="50" value="{searchString}">
    <input class="submit" type="submit" name="submit" value="{msgSearch}">
    
    </fieldset>
	</form>
    </p>
	<p>{printResult}</p>
	