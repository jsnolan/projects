<?php
session_start();

require('./Base.php');
require('./Exception.php');
require('./StanfordTagger.php');
require('./NERTagger.php');

require('./POSTagger.php');

require_once('./twitteroauth/twitteroauth.php');
require_once('./config.php');
	
require_once('./autoload.php');
$sentiment = new \PHPInsight\Sentiment();

$access_token = $_SESSION['access_token'];
$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, ACCESS_KEY, ACCESS_SECRET);


?>

<!DOCTYPE html>
<meta charset="utf-8">
<title>Web Speech API Demo</title>
<style>
  * {
    font-family: Verdana, Arial, sans-serif;
  }
  a:link {
    color:#000;
    text-decoration: none;
  }
  a:visited {
    color:#000;
  }
  a:hover {
    color:#33F;
  }
  .button {
    background: -webkit-linear-gradient(top,#008dfd 0,#0370ea 100%);
    border: 1px solid #076bd2;
    border-radius: 3px;
    color: #fff;
    display: none;
    font-size: 13px;
    font-weight: bold;
    line-height: 1.3;
    padding: 8px 25px;
    text-align: center;
    text-shadow: 1px 1px 1px #076bd2;
    letter-spacing: normal;
  }
  .center {
    padding: 10px;
    text-align: center;
  }
  .final {
    color: black;
    padding-right: 3px; 
  }
  .interim {
    color: gray;
  }
  .info {
    font-size: 14px;
    text-align: center;
    color: #777;
    display: none;
  }
  .right {
    float: right;
  }
  .sidebyside {
    display: inline-block;
    width: 45%;
    min-height: 40px;
    text-align: left;
    vertical-align: top;
  }
  #headline {
    font-size: 40px;
    font-weight: 300;
  }
  #info {
    font-size: 20px;
    text-align: center;
    color: #777;
    visibility: hidden;
  }
  #results {
    font-size: 14px;
    font-weight: bold;
    border: 1px solid #ddd;
    padding: 15px;
    text-align: left;
    min-height: 150px;
  }
  #start_button {
    border: 0;
    background-color:transparent;
    padding: 0;
  }
</style>
<h1 class="center" id="headline">
  <a href="http://dvcs.w3.org/hg/speech-api/raw-file/tip/speechapi.html">
    AI Assignment</a> 1</h1>
<div id="info">
  <p id="info_start">Click on the microphone icon and begin speaking.</p>
  <p id="info_speak_now">Speak now.</p>
  <p id="info_no_speech">No speech was detected. You may need to adjust your
    <a href="//support.google.com/chrome/bin/answer.py?hl=en&amp;answer=1407892">
      microphone settings</a>.</p>
  <p id="info_no_microphone" style="display:none">
    No microphone was found. Ensure that a microphone is installed and that
    <a href="//support.google.com/chrome/bin/answer.py?hl=en&amp;answer=1407892">
    microphone settings</a> are configured correctly.</p>
  <p id="info_allow">Click the "Allow" button above to enable your microphone.</p>
  <p id="info_denied">Permission to use microphone was denied.</p>
  <p id="info_blocked">Permission to use microphone is blocked. To change,
    go to chrome://settings/contentExceptions#media-stream</p>
  <p id="info_upgrade">Web Speech API is not supported by this browser.
     Upgrade to <a href="//www.google.com/chrome">Chrome</a>
     version 25 or later.</p>
</div>
<div class="right">
  <button id="start_button" onclick="startButton(event)">
    <img id="start_img" src="mic.gif" alt="Start"></button>
</div>
<div id="results">
  <span id="final_span" class="final"></span>
  <span id="interim_span" class="interim"></span>
  <p>
</div>
<div hidden class="center">
  <div class="sidebyside" style="text-align:right">
    <button id="copy_button" class="button" onclick="copyButton()">
      Copy and Paste</button>
    <div id="copy_info" class="info">
      Press Control-C to copy text.<br>(Command-C on Mac.)
    </div>
  </div>
  <div class="sidebyside">
    <button id="email_button" class="button" onclick="emailButton()">
      Create Email</button>
    <div id="email_info" class="info">
      Text sent to default email application.<br>
      (See chrome://settings/handlers to change.)
    </div>
  </div>
  <p>
  <div id="div_language">
    <select id="select_language" onchange="updateCountry()"></select>
    &nbsp;&nbsp;
    <select id="select_dialect"></select>
  </div>
</div>

<script>
var langs =
[['Afrikaans',       ['af-ZA']],
 ['Bahasa Indonesia',['id-ID']],
 ['Bahasa Melayu',   ['ms-MY']],
 ['Català',          ['ca-ES']],
 ['Čeština',         ['cs-CZ']],
 ['Deutsch',         ['de-DE']],
 ['English',         ['en-AU', 'Australia'],
                     ['en-CA', 'Canada'],
                     ['en-IN', 'India'],
                     ['en-NZ', 'New Zealand'],
                     ['en-ZA', 'South Africa'],
                     ['en-GB', 'United Kingdom'],
                     ['en-US', 'United States']],
 ['Español',         ['es-AR', 'Argentina'],
                     ['es-BO', 'Bolivia'],
                     ['es-CL', 'Chile'],
                     ['es-CO', 'Colombia'],
                     ['es-CR', 'Costa Rica'],
                     ['es-EC', 'Ecuador'],
                     ['es-SV', 'El Salvador'],
                     ['es-ES', 'España'],
                     ['es-US', 'Estados Unidos'],
                     ['es-GT', 'Guatemala'],
                     ['es-HN', 'Honduras'],
                     ['es-MX', 'México'],
                     ['es-NI', 'Nicaragua'],
                     ['es-PA', 'Panamá'],
                     ['es-PY', 'Paraguay'],
                     ['es-PE', 'Perú'],
                     ['es-PR', 'Puerto Rico'],
                     ['es-DO', 'República Dominicana'],
                     ['es-UY', 'Uruguay'],
                     ['es-VE', 'Venezuela']],
 ['Euskara',         ['eu-ES']],
 ['Français',        ['fr-FR']],
 ['Galego',          ['gl-ES']],
 ['Hrvatski',        ['hr_HR']],
 ['IsiZulu',         ['zu-ZA']],
 ['Íslenska',        ['is-IS']],
 ['Italiano',        ['it-IT', 'Italia'],
                     ['it-CH', 'Svizzera']],
 ['Magyar',          ['hu-HU']],
 ['Nederlands',      ['nl-NL']],
 ['Norsk bokmål',    ['nb-NO']],
 ['Polski',          ['pl-PL']],
 ['Português',       ['pt-BR', 'Brasil'],
                     ['pt-PT', 'Portugal']],
 ['Română',          ['ro-RO']],
 ['Slovenčina',      ['sk-SK']],
 ['Suomi',           ['fi-FI']],
 ['Svenska',         ['sv-SE']],
 ['Türkçe',          ['tr-TR']],
 ['български',       ['bg-BG']],
 ['Pусский',         ['ru-RU']],
 ['Српски',          ['sr-RS']],
 ['한국어',            ['ko-KR']],
 ['中文',             ['cmn-Hans-CN', '普通话 (中国大陆)'],
                     ['cmn-Hans-HK', '普通话 (香港)'],
                     ['cmn-Hant-TW', '中文 (台灣)'],
                     ['yue-Hant-HK', '粵語 (香港)']],
 ['日本語',           ['ja-JP']],
 ['Lingua latīna',   ['la']]];
for (var i = 0; i < langs.length; i++) {
  select_language.options[i] = new Option(langs[i][0], i);
}
select_language.selectedIndex = 6;
updateCountry();
select_dialect.selectedIndex = 6;
showInfo('info_start');
function updateCountry() {
  for (var i = select_dialect.options.length - 1; i >= 0; i--) {
    select_dialect.remove(i);
  }
  var list = langs[select_language.selectedIndex];
  for (var i = 1; i < list.length; i++) {
    select_dialect.options.add(new Option(list[i][1], list[i][0]));
  }
  select_dialect.style.visibility = list[1].length == 1 ? 'hidden' : 'visible';
}
var create_email = false;
var final_transcript = '';
var recognizing = false;
var ignore_onend;
var start_timestamp;
var play = false;
var u = new SpeechSynthesisUtterance();
var response1 = false;
var response2 = false;
var response3 = false;
var response4 = false;
var response5 = false;
if (!('webkitSpeechRecognition' in window)) {
  upgrade();
} else {
  start_button.style.display = 'inline-block';
  var recognition = new webkitSpeechRecognition();
  recognition.continuous = true;
  recognition.interimResults = true;
  recognition.onstart = function() {
    recognizing = true;
    showInfo('info_speak_now');
    start_img.src = 'mic-animate.gif';
  };
  recognition.onerror = function(event) {
    if (event.error == 'no-speech') {
      start_img.src = 'mic.gif';
      showInfo('info_no_speech');
      ignore_onend = true;
    }
    if (event.error == 'audio-capture') {
      start_img.src = 'mic.gif';
      showInfo('info_no_microphone');
      ignore_onend = true;
    }
    if (event.error == 'not-allowed') {
      if (event.timeStamp - start_timestamp < 100) {
        showInfo('info_blocked');
      } else {
        showInfo('info_denied');
      }
      ignore_onend = true;
    }
  };
  recognition.onend = function() {
    recognizing = false;
    if (ignore_onend) {
      return;
    }
    start_img.src = 'mic.gif';
    if (!final_transcript) {
      showInfo('info_start');
      return;
    }
    showInfo('');
    if (window.getSelection) {
      window.getSelection().removeAllRanges();
      var range = document.createRange();
      range.selectNode(document.getElementById('final_span'));
      window.getSelection().addRange(range);
    }
    if (create_email) {
      create_email = false;
      createEmail();
    }

  };
  recognition.onresult = function(event) {
    var interim_transcript = '';
    for (var i = event.resultIndex; i < event.results.length; ++i) {
      if (event.results[i].isFinal) {
        final_transcript += event.results[i][0].transcript;
      } else {
        interim_transcript += event.results[i][0].transcript;
      }
    }
    final_transcript = capitalize(final_transcript);
    final_span.innerHTML = linebreak(final_transcript);
    interim_span.innerHTML = linebreak(interim_transcript);
    if (final_transcript || interim_transcript) {
      showButtons('inline-block');
    }
    
    if (final_transcript)
    {
    	ordered_response();
    }
  };
}

function ordered_response() {
	recognition.stop();
	
	u.lang = recognition.lang;
    u.rate = 1.2;
    u.volume=1;
    u.text = "I don't understand.";
	
	//speechSynthesis.speak(u);
}

function upgrade() {
  start_button.style.visibility = 'hidden';
  showInfo('info_upgrade');
}
var two_line = /\n\n/g;
var one_line = /\n/g;
function linebreak(s) {
  return s.replace(two_line, '<p></p>').replace(one_line, '<br>');
}
var first_char = /\S/;
function capitalize(s) {
  return s.replace(first_char, function(m) { return m.toUpperCase(); });
}
function createEmail() {
  var n = final_transcript.indexOf('\n');
  if (n < 0 || n >= 80) {
    n = 40 + final_transcript.substring(40).indexOf(' ');
  }
  var subject = encodeURI(final_transcript.substring(0, n));
  var body = encodeURI(final_transcript.substring(n + 1));
  window.location.href = 'mailto:?subject=' + subject + '&body=' + body;
}
function copyButton() {
  if (recognizing) {
    recognizing = false;
    recognition.stop();
  }
  copy_button.style.display = 'none';
  copy_info.style.display = 'inline-block';
  showInfo('');
}
function emailButton() {
  if (recognizing) {
    create_email = true;
    recognizing = false;
    recognition.stop();
  } else {
    createEmail();
  }
  email_button.style.display = 'none';
  email_info.style.display = 'inline-block';
  showInfo('');
}
function startButton(event) {
  if (recognizing) {
    recognition.stop();
    return;
  }
  final_transcript = '';
  recognition.lang = select_dialect.value;
  recognition.start();
  ignore_onend = false;
  final_span.innerHTML = '';
  interim_span.innerHTML = '';
  start_img.src = 'mic-slash.gif';
  showInfo('info_allow');
  showButtons('none');
  start_timestamp = event.timeStamp;
}
function showInfo(s) {
  if (s) {
    for (var child = info.firstChild; child; child = child.nextSibling) {
      if (child.style) {
        child.style.display = child.id == s ? 'inline' : 'none';
      }
    }
    info.style.visibility = 'visible';
  } else {
    info.style.visibility = 'hidden';
  }
}
var current_style;
function showButtons(style) {
  if (style == current_style) {
    return;
  }
  current_style = style;
  copy_button.style.display = style;
  email_button.style.display = style;
  copy_info.style.display = 'none';
  email_info.style.display = 'none';
}
</script>

<script>	//SCRIPT TO SUMMARISE WIKIPEDIA PAGES
/* tldr.js copyright Seth Raphael 2011 */

function initsumm () {
    initStopList();
    var paragraphs = document.getElementsByTagName('p');
    var rootContent;
    if (!rootContent) rootContent = document.getElementById('post'); /*blogs*/
    if (!rootContent) rootContent = document.getElementById('content'); /*wikipedia*/
    if (!rootContent) rootContent = document.getElementById('article'); /*NYTimes*/
    if (!rootContent) rootContent = document.getElementById('articles');
    if (!rootContent) rootContent = document.getElementById('cnnContentContainer'); /*wikipedia*/
    if (!rootContent) rootContent = document.getElementById('main'); /*wikipedia*/
    if (!rootContent) rootContent = document.getElementById('page'); /*wikipedia*/
    if (!rootContent) rootContent = document; /*everythingelse*/    
    paragraphs = rootContent.getElementsByTagName('p');;
     thetext = '';
     var count = 0;
    for (var i = 0; i< paragraphs.length; i++) {
        newtext = parseps(paragraphs[i]);
        if (newtext.indexOf(". ") !=-1 || newtext.indexOf(".") == newtext.length){
            thetext += newtext +". ";
            count ++;
        }
    }
    d=window.getSelection()+'';
    if (d) parseit (d);
    else parseit(thetext);
    showit();
}

function parseps (n) {
    if (n.nodeType == 3) {
        if (n.data) {
            return n.data + " ";
        } else return ;
    }
    var children = n.childNodes;               
    var text ='';
    for(var i=0; i < children.length; i++) {   
        text += parseps(children[i]);     
    }
    return text;
}
var sentences;
var sorted_scores;
function parseit(text) {
    sentences = findsentences(text);
    var dictionary = new Array();
    for (i=0; i<sentences.length; i++) {
        var my_words = findwords(sentences[i]);
        for (j=0; j<my_words.length; j++) {
            if (!dictionary[my_words[j].toLowerCase()]) dictionary[my_words[j].toLowerCase()]=0;
            dictionary[my_words[j].toLowerCase()]++;
        }
    }
    var scores = new Array();
    var topsentence = 0;
    for (i=0; i<sentences.length; i++) {
        scores[i] = 0;                    
        var count = 0;
        var my_words = findwords(sentences[i]);
        var goodwords = 0;
        for (j=0; j<my_words.length; j++) {
            if (my_words[j].length >3) {
                if (!dictionary[my_words[j].toLowerCase()]) dictionary[my_words[j].toLowerCase()]=0;
                var rx = new RegExp(" " + my_words[j].toLowerCase().replace(/[^a-zA-Z 0-9]+/g,'') + " ");
                if (!rx.test(stoplist)) { 
                        scores[i] += dictionary[my_words[j].toLowerCase()];
                        goodwords++;
                    }
                count ++;
            }
        }
        scores[i]/=count;
        if (goodwords < 2) {
            scores[i] = 0;
        };
    }
    /*alert (sentences);*/
    sorted_scores = new Array();
    i = 0;
    for(i = 0; i < scores.length; i++) {
     sorted_scores[i] = [scores[i], i];
    }
    sorted_scores.sort(sortit);
}
function showit() {
    var summary;
    var fullsummary;
    
    if (!summary) {
        summary = document.createElement('div');
        summary.style.position = "fixed";
        summary.style.fontSize = "1em";
        summary.style.bottom = "0px";
        summary.style.width = "100%";
        summary.style.backgroundColor = "lightblue";
        summary.style.color = "black";
        summary.style.zIndex = "9999999999";
        document.body.appendChild(summary);
    }
    summary.innerHTML = "<ol>";
    summary.innerHTML += "<li>Summary</li>";
    for (var i=4; i < 7; i++) {
        var thissentence = sentences[sorted_scores[i][1]];
        var thisscore = sorted_scores[i][0];
        summary.innerHTML += "<li>"  + thissentence + "</li>";
        fullsummary += thissentence+'. ';
        var u = new SpeechSynthesisUtterance();
        u.text = thissentence;
        speechSynthesis.speak(u);
    };
    
    summary.innerHTML += "</ol>";
    
}

function findsentences (text) {
    
    var t = text.replace(/\b(.)\./g, " $1");
    t = t.replace(/\.(.)\./g,"$1");
    var sentences = t.split(/\. /);
    return sentences;
}
function findwords (text) {
    var words = text.split(" ");
    return words;
}

function sortit (a,b) {
    var aa;
    var bb;
    if (!a[0]) {
        aa = 0;
    } else {
        aa = a[0]; 
    }
    if (!b[0]) {
        bb = 0;
    } else {
        bb = b[0]; 
    }
    return bb - aa;   
}
function initStopList() {
    stoplist=" 'll 've 10 39 a able about above abst accordance according accordingly across act actually ad added adj adopted ae af affected affecting affects after afterwards ag again against ah ai al all almost alone along already also although always am among amongst an and announce another any anybody anyhow anymore anyone anything anyway anyways anywhere ao apparently approximately aq ar are aren aren't arent arise around arpa as aside ask asking at au auth available aw away awfully az b ba back bb bd be became because become becomes becoming been before beforehand begin beginning beginnings begins behind being believe below beside besides between beyond bf bg bh bi billion biol bj bm bn bo both br brief briefly bs bt but buy bv bw by bz c ca came can can't cannot caption cause causes cc cd certain certainly cf cg ch ci ck cl click cm cn co co. com come comes contain containing contains copy could couldn couldn't couldnt cr cs cu cv cx cy cz d date de did didn didn't different dj dk dm do does doesn doesn't doing don don't done down downwards due during dz e each ec ed edu ee effect eg eh eight eighty either else elsewhere end ending enough er es especially et et-al etc even ever every everybody everyone everything everywhere ex except f far few ff fi fifth fifty find first five fix fj fk fm fo followed following follows for former formerly forth forty found four fr free from further furthermore fx g ga gave gb gd ge get gets getting gf gg gh gi give given gives giving gl gm gmt gn go goes gone got gotten gov gp gq gr gs gt gu gw gy h had happens hardly has hasn hasn't have haven haven't having he he'd he'll he's hed help hence her here here's hereafter hereby herein heres hereupon hers herself hes hi hid him himself his hither hk hm hn home homepage how howbeit however hr ht htm html http hu hundred i i'd i'll i'm i've i.e. id ie if ii il im immediate immediately importance important in inc inc. indeed index information instead int into invention inward io iq ir is isn isn't it it'll it's itd its itself j je jm jo join jp just k ke keep keeps kept keys kg kh ki km kn know known knows kp kr kw ky kz l la largely last lately later latter latterly lb lc least less lest let let's lets li like liked likely line little lk ll look looking looks lr ls lt ltd lu lv ly m ma made mainly make makes many may maybe mc md me mean means meantime meanwhile merely mg mh microsoft might mil million miss mk ml mm mn mo more moreover most mostly mp mq mr mrs ms msie mt mu much mug must mv mw mx my myself mz n na name namely nay nc nd ne near nearly necessarily necessary need needs neither net netscape never nevertheless new next nf ng ni nine ninety nl no nobody non none nonetheless noone nor normally nos not noted nothing now nowhere np nr nu NULL nz o obtain obtained obviously of off often oh ok okay old om omitted on once one one's ones only onto or ord org other others otherwise ought our ours ourselves out outside over overall owing own p pa page pages part particular particularly past pe per perhaps pf pg ph pk pl placed please plus pm pn poorly possible possibly potentially pp pr predominantly present previously primarily probably promptly proud provides pt put pw py q qa que quickly quite qv r ran rather rd re readily really recent recently ref refs regarding regardless regards related relatively research reserved respectively resulted resulting results right ring ro ru run rw s sa said same saw say saying says sb sc sd se sec section see seeing seem seemed seeming seems seen self selves sent seven seventy several sg sh shall she she'd she'll she's shed shes should shouldn shouldn't show showed shown showns shows si significant significantly similar similarly since site six sixty sj sk sl slightly sm sn so some somebody somehow someone somethan something sometime sometimes somewhat somewhere soon sorry specifically specified specify specifying sr st state states still stop strongly su sub substantially successfully such sufficiently suggest sup sure sv sy sz t take taken taking tc td tell ten tends test text tf tg th than thank thanks thanx that that'll that's that've thats the their theirs them themselves then thence there there'll there's there've thereafter thereby thered therefore therein thereof therere theres thereto thereupon these they they'd they'll they're they've theyd theyre think thirty this those thou though thoughh thousand three throug through throughout thru thus til tip tj tk tm tn to together too took toward towards tp tr tried tries trillion truly try trying ts tt tv tw twenty twice two tz u ua ug uk um un under unfortunately unless unlike unlikely until unto up upon ups us use used useful usefully usefulness uses using usually uy uz v va value various vc ve very vg vi via viz vn vol vols vs vu w want wants was wasn wasn't way we we'd we'll we're we've web webpage website wed welcome well went were weren weren't wf what what'll what's whatever whats when whence whenever where whereafter whereas whereby wherein wheres whereupon wherever whether which while whim whither who who'd who'll who's whod whoever whole whom whomever whos whose why widely will willing wish with within without won won't words world would wouldn wouldn't ws www x y ye yes yet you you'd you'll you're you've youd your youre yours yourself yourselves yt yu z za zero zm zr times subscribe twitter log login";
}
</script>

<form name="form" action="" method="post">
Search what you said.
<br>
<script type="text/javascript">
document.write('<input type="hidden" name="my_text" value="">');
</script>
<input type="submit" onclick="setValue();">
</form>

<script>
function setValue(){
	document.form.my_text.value = final_transcript;
	document.forms["form"].submit();
}
</script>

<?php
	$mySentence = $_POST['my_text'];			//Store the stated sentence
	$query = "";								//Store the topic of the query
	$pos = new \StanfordNLP\POSTagger('./models/english-left3words-distsim.tagger','./stanford-postagger.jar');
	$ner = new \StanfordNLP\NERTagger(
	'./classifiers/english.all.3class.distsim.crf.ser.gz',
	'./stanford-ner.jar'
	);											//Used for tagging the topic as either a person, organization or location
	$curl = curl_init();
	$i=0;										//Used to navigate through loops
	$valid = false;								//Used to check if a stated sentence is valid, defaults as invalid
	
	//EXTRACT SPOKEN WORDS
	if (substr( $mySentence, 0, 8 ) === "What is ")		//Identify the topic
	{
		$valid = true;
		$position = strpos($mySentence, "is ");
		$query = substr($mySentence, $position+strlen("is "));  
		$query = ucwords($query);
		
		//echo $query;
		$_SESSION["query"] = $query;	//Store the search term in the session
		echo "<br>";
		$firstload = true;				//Page has been loaded the first time
	}	
	
	
	
	//FIND THE TAG OF THE USERS SPOKEN WORD, after the page is loaded the first time
	if ($firstload)
	{
		$result = $ner->tag(explode(' ', $query));		//Use NER tagging to identify the tag of the topic
		$tag = $result[0][1];							//Store the tag in a variable
	
		if (($query == "Adidas") || ($query == "Samsung"))	//HARD CODING THESE AS THEY ARE CLASSIFIED INCORRECTLY BY NER TAGGER
		{
			$tag = "ORGANIZATION";
		}
		
		// CHECK IF THE SPOKEN WORD IS A PERSON, LOCATION OR ORGANIZATION. Tell the user
		if ($tag == "PERSON")
		{
			echo $query." is a person";
		}
		else if ($tag == "LOCATION")
		{
			echo $query." is a location";
		}
		else if ($tag == "ORGANIZATION")
		{
			echo $query." is a organization";
		}
		
		$_SESSION["tag"] = $tag;	//Store the search terms tag in the session
		
		$_SESSION["query"] = str_replace(' ', '_', $_SESSION["query"]);		//swap spaces with underscores so it works with wikipedia URL
		
		if (strpos($_SESSION["query"],'Inc') !== false)	//ADD FULL STOP IF IT IS AN INC COMPANY (LIKE APPLE), SO URL WORKS
		{
    		$_SESSION["query"] .= ".";
		}
		echo "<br>";
	}
	
	if ($_SESSION["query"])		//Tell the user what the topic is
	{
		echo "Your topic is ".$_SESSION["query"]."<br>";
	}
	
	if ($query)	//SEARCH THE TERM IN WIKIPEDIA
	{
		$tempquery = $_SESSION["query"];
		curl_setopt($curl, CURLOPT_URL, "https://en.wikipedia.org/wiki/$tempquery");
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
	
		$contents = curl_exec($curl);	//STORE THE WIKIPEDIA PAGE DATA
		
		$_SESSION["wikipage"] = $contents;	//STORE WIKIPEDIA PAGE IN SESSION
	}
	
	$dom = new DOMDocument();	
	@$dom->loadHTML($_SESSION["wikipage"]);	//LOAD WIKIPEDIA PAGE FROM SESSION
	
	//SUMMARISE THE WIKIPEDIA CONTENT IF THE WORD IS TAGGED, after the page is loaded the first time
	if ($firstload)	//IF FIRST RUN THROUGH
	{
		//RUN WIKIPEDIA SUMMARISATION SCRIPT
		?>
		<div hidden>
		<?php
		//PUT WIKIPEDIA CONTENT IN DOCUMENT SO IT CAN BE SUMMARISED, BUT HIDE IT
		echo $contents;
		?>
		</div>
		<script>
			void (initsumm());
		</script>
		<?php
	}
	
	//EXTRACTING INFORMATION FOR AN ORGANIZATION
	if ($_SESSION["tag"] == "ORGANIZATION")
	{
		$tagtype = "tr";				//Type of markup to look for in Wikipedia page
		if (stripos($mySentence, 'founded') !== false)	//SHOW WHEN IT WAS FOUNDED
		{
			$valid = true;
			$searchterm = "founded";	//Word to look for in the Wikipedia page
		}
		else if (stripos($mySentence, 'founder') !== false)	//SHOW WHO ARE THE FOUNDERS
		{
			$valid = true;
			$searchterm = "founder";
		}
		else if (stripos($mySentence, 'located') !== false)	//SHOW WHERE IT IS LOCATED
		{
			$valid = true;
			$searchterm = "headquarters";
		}
		extractinfo($dom, $searchterm, $tagtype);	//Use function to extract the info
	}
	
	
	//EXTRACTING INFORMATION FOR LOCATION
	if ($_SESSION["tag"] == "LOCATION")	//FOLLOW UP QUESTIONS FOR LOCATION
	{
		if (stripos($mySentence, 'people') !== false)		//SHOW POPULATION
		{
			$valid = true;
			$searchterm = "population";
			$tagtype = "tr";
		}
		
		else if (stripos($mySentence, 'established') !== false)		//SHOW ESTABLISH DATE
		{
			$valid = true;
			$searchterm = "established";
			$tagtype = "tr";
		}
		
		else if (stripos($mySentence, 'country') !== false)			//SHOW COUNTRY
		{
			$valid = true;
			$searchterm = "is the";
			$tagtype = "p";
		}
		extractinfo($dom,$searchterm,$tagtype);
	}
	
	//EXTRACTING INFORMATION FOR PERSON
	if ($_SESSION["tag"] == "PERSON")	//FOLLOW UP QUESTIONS FOR PERSON
	{
		$tagtype = "tr";
		
		if (stripos($mySentence, 'old') !== false)		//SHOW AGE
		{
			$valid = true;
			$searchterm = "born";
		}
		
		if (stripos($mySentence, 'from') !== false)		//SHOW BIRTH PLACE
		{
			$valid = true;
			$searchterm = "born";
		}
		
		if (stripos($mySentence, 'spouse') !== false)		//SHOW SPOUSE
		{
			$valid = true;
			$searchterm = "spouse";
		}
		extractinfo($dom, $searchterm, $tagtype);
	}	
	
	//CHECK EMERGING EVENTS ON TWITTER
	if (stripos($mySentence, 'events') !== false)
	{ 
		$valid = true;
		echo "<br><br>";
		speak("Recent tweets: ");
		echo "<br><br>";
		$contents = $connection->get("https://api.twitter.com/1.1/search/tweets.json?q=".$_SESSION["query"]."&result_type=recent&locate=en&count=2000");
		
		$decode=$contents;
		
		foreach ($decode->statuses as $result)
		{
			$onehourago = date("H:i:s", time()-3600);
			$createdtime = strtotime($result->created_at);
			$formattedcreatedtime = date("H:i:s", $createdtime);
			
			
			if ($formattedcreatedtime > $onehourago)	//If the tweet was created less than one hour ago
			{
				echo $formattedcreatedtime."<br>";
				$tweetText = $result->text;
	  			echo $tweetText . "<br><br>";
	  			$fulltext .= $tweetText." ";
			}
		}
		
		preg_match_all('/(?<!\w)#\w+/',$fulltext,$hashtags);	//extract all hashtags
		
		for ($i=0; $i<sizeof($hashtags[0]); $i++)	//store all hashtags in a single string
		{
			$fullhashtagtext .= " ".$hashtags[0][$i];
		}
		
		//TERM FREQUENCY ANALYSIS OF HASHTAGS
		$sortedhashtags = array_count_values(explode(' ', $fullhashtagtext));
		arsort($sortedhashtags);
		
		$topfivesortedhashtags = array_slice($sortedhashtags, 0, 5);
		
		$keys = array_keys($topfivesortedhashtags);	//Store the hashtags in an array
		
		$_SESSION['topfivesortedhashtags'] = $keys;	//STORE TOP FIVE SORTED HASHTAGS ARRAY IN SESSION
		
		speak("Top five emerging topic terms are: ");
		echo "<br><br>";
		for ($i=0; $i<sizeof($_SESSION['topfivesortedhashtags']); $i++)	//PRINT TOP 5 HASHTAGS
		{
			speak($_SESSION['topfivesortedhashtags'][$i]);
			echo " "."<br>";
		}
	}
	
	
	
	//SHOW MORE DETAIL ABOUT EMERGING HASHTAGS
	if (stripos($mySentence, 'detailed') !== false)	//KEYWORD DETAILED TO CHECK EMERGING TOPIC TERMS
	{ 
		for ($i=0; $i<sizeof($_SESSION['topfivesortedhashtags']); $i++)	//TRAVERSE THROUGH ALL SORTED HASHTAGS
		{
			$strippedhashtag = ltrim ($_SESSION['topfivesortedhashtags'][$i], '#');	//STRIP THE HASHTAG CHARACTER FROM THE STRING
			if (stripos($mySentence, $strippedhashtag) !== false)	//Check if the stated sentence contains one of the hashtags
			{	
				$valid = true;
				echo "<br>";
				speak("More detailed information about $strippedhashtag is shown below");
				echo "<br><br>";
				
				$contents = $connection->get("https://api.twitter.com/1.1/search/tweets.json?q=".$strippedhashtag."&result_type=recent&locate=en&count=100");
				$decode=$contents;
				
				$_SESSION['detailedtweets'] = $decode;
		
				foreach ($decode->statuses as $result)	//PRINT THE TWEETS OF RELATED TOPIC
				{
					$onehourago = date("H:i:s", time()-3600);
					$createdtime = strtotime($result->created_at);
					$formattedcreatedtime = date("H:i:s", $createdtime);
					if ($formattedcreatedtime > $onehourago)	//If the tweet was created less than one hour ago
					{
						$tweetText = $result->text;
			  			$scores = $sentiment->score($tweetText);		//Sentimental analysis of tweets
						$class = $sentiment->categorise($tweetText);
						echo $formattedcreatedtime."<br>";
						
						$totalTweetText .= $tweetText." ";
						
						//SUPERVISED LEARNING PATTERN IMPLEMENTATION
						if ((stripos($tweetText, "can't") !== false) && (stripos($tweetText, "cute") !== false))
						{
							$class = "positive";
						}
						else if ((stripos($tweetText, "no") === false) && (stripos($tweetText, "cute") !== false))
						{
							$class = "positive";
						}
						else if ((stripos($tweetText, "sad") === false) && (stripos($tweetText, "can't") !== false) && (stripos($tweetText, "wait") !== false))
						{
							$class = "positive";
						}
						
	
						// output:
						echo "Tweet: $tweetText <br>";
						echo "Dominant: $class <br>";
						echo "Scores:<br>";
						echo "<pre>";
						print_r($scores);
						echo "</pre>";
						echo "<br><br><br>";
						
						switch($class)	//Count the number of positive, negative and neutral tweets
						{
							case "neu":
								$neu++;
								break;
							case "pos":
								$pos++;
								break;
							case "neg":
								$neg++;
								break;
						}
					}
				}
				
				//Calculate percentages for each opinion type
				$total = $pos+$neg+$neu;
				$posPercent = round($pos/$total*100);
				$negPercent = round($neg/$total*100);
				$neuPercent = round($neu/$total*100);
				speak("$posPercent % positive, $negPercent % negative, $neuPercent % neutral");
				
				
				//FIND TEN MOST REPRESENTATIVE NOUNS AND SPEAK THEM, USES POS TAGGING
				$result = $pos->tag(explode(' ',$totalTweetText));
								
				foreach ($result as $inner_arr)
				{ 
					$value = $inner_arr[1];
					if (stripos($value, "NN") !== false)
					{
						$allnouns .= $inner_arr[0]." ";	//String of all nouns
					}
				}
								
				$popularnouns = array_count_values(explode(' ', $allnouns));
				arsort($popularnouns);
		
				$toptensortednouns = array_slice($popularnouns, 0, 10);
				
				$keys = array_keys($toptensortednouns);	//Store the hashtags in an array
				
				echo "<br><br>";
				speak("The most representative nouns on this topic are: ");
				echo "<br>";
				
				foreach ($keys as $noun)
				{
					speak($noun);
					echo "<br>";
				}
						
				break;
			}
		}
	}
	
	//USE POS TAGGER TO FIND NOUNS
	//SHOW SENTIMENTAL ANALYSIS OF DETAILED TWEETS, BASED ON IF USER ASKS FOR POSITIVE, NEGATIVE OR NEUTRAL
	if (stripos($mySentence, 'positive') !== false)	//KEYWORD POSITIVE TO SHOW SPECIFIC SENTIMENTS
	{ 
		$valid = true;
		sentimentalanalysis("positive", $sentiment);
	}
	else if (stripos($mySentence, 'negative') !== false)	//KEYWORD NEGATIVE TO SHOW SPECIFIC SENTIMENTS
	{ 
		$valid = true;
		sentimentalanalysis("negative", $sentiment);
	}
	else if (stripos($mySentence, 'neutral') !== false)	//KEYWORD NEUTRAL TO SHOW SPECIFIC SENTIMENTS
	{ 
		$valid = true;
		sentimentalanalysis("neutral", $sentiment);
	}
	
	if (($valid != true) && ($_POST['my_text']))	//Print that the message was invalid if the user has submitted an invalid message
	{
		//echo "Sorry, I did not understand.";
		speak("Sorry, I did not understand.");
	}
	
	
	//USER WRITTEN FUNCTIONS
	function speak($mymessage)	//FUNCTION TO SPEAK THE PARSED STRING
	{
		echo $mymessage;
		?>
		<script>
		var u = new SpeechSynthesisUtterance();
		u.text = "<?php echo $mymessage; ?>";
		speechSynthesis.speak(u);
		</script>
		<?php
	}
	
	function extractinfo($dom, $searchterm, $tagtype)	//FUNCTION TO EXTRACT INFORMATION FROM WIKIPEDIA
	{
		foreach($dom->getElementsByTagName("$tagtype") as $para)	//FETCH INFO
			{
				if (stripos($para->nodeValue, "$searchterm") !== false)	//IF THE ELEMENT CONTAINS THE WORD
				{
					speak($para->nodeValue);
					//echo $para->nodeValue;								//PRINT IT OUT
					echo "<br />";
					break;												//AND LEAVE THE FOREACH
				}
			}
	}	
	
	function sentimentalanalysis($opinion, $sentiment)		//SHOW SENTIMENTAL ANALYSIS OF THE TWEETS OF THE GIVEN OPINION. INCLUDES SUPERVISED LEARNING PATTERN
	{
		echo "<br>";
		speak("Showing all $opinion tweets");
		echo "<br><br>";
		
		$decode = $_SESSION['detailedtweets'];
		foreach ($decode->statuses as $result)	//PRINT THE TWEETS OF RELATED TOPIC
		{
			$onehourago = date("H:i:s", time()-3600);
			$createdtime = strtotime($result->created_at);
			$formattedcreatedtime = date("H:i:s", $createdtime);
			
			
			if ($formattedcreatedtime > $onehourago)	//If the tweet was created less than one hour ago
			{
				$tweetText = $result->text;
			  	$scores = $sentiment->score($tweetText);
				$class = $sentiment->categorise($tweetText);
				
				//SUPERVISED LEARNING PATTERN IMPLEMENTATION
				if ((stripos($tweetText, "can't") !== false) && (stripos($tweetText, "cute") !== false))
				{
					$class = "positive";
				}
				else if ((stripos($tweetText, "no") === false) && (stripos($tweetText, "cute") !== false))
				{
					$class = "positive";
				}
				else if ((stripos($tweetText, "sad") === false) && (stripos($tweetText, "can't") !== false) && (stripos($tweetText, "wait") !== false))
				{
					$class = "positive";
				}
			
				if ($class == substr("$opinion", 0, 3))
				{
					echo $formattedcreatedtime."<br>";
					echo "String: $tweetText <br>";
					echo "Dominant: $class, scores:";
					echo "<pre>";
					print_r($scores);
					echo "</pre>";
					echo "<br><br><br>";	
				}
			}
		}
	}	
?>