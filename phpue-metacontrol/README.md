<div style="font-family: Arial, sans-serif; line-height: 1.6; max-width: 900px; margin: 2rem auto; padding: 0 1rem;">
  <h1 style="color: #333;">MetaControl in PHPue</h1>

  <!-- Warning Banner -->
  <div style="background: #fff3cd; border-left: 6px solid #ffeeba; padding: 1rem; margin-bottom: 1rem; border-radius: 4px;">
    ⚠️ <strong>Compatibility Notice:</strong> This extension relates to the issue 
    <a href="https://github.com/ShinkuKira21/PHPue/issues/6">"Known Header Issue - Injects Static HTML instead of Dynamic PHP #6"</a>. 
    It <strong>won't work</strong> if you are using PHPue Framework Alpha (No Version Number).
  </div>

  <h2 style="color: #333;">📥 Installation</h2>
  <p>Upload to your PHPue project:</p>
  <ul>
    <li>Add <code>phpue-metacontrol.ext.php</code> to <code>backend/</code></li>
    <li>Already loaded and ready to use</li>
  </ul>

  <p><strong>MetaControl</strong> is PHPue's built-in class for managing page-level metadata dynamically. It allows you to control titles, meta descriptions, keywords, and other variables across your pages without repeating code.</p>

  <h2 style="color: #333;">⚙️ How It Works</h2>
  <ol>
    <li>
      <strong>Get the MetaControl instance:</strong>
      <pre style="background: #f4f4f4; padding: 1rem; border-radius: 6px; overflow-x: auto;"><code>$pageMeta = \PHPueExt\MetaControl::getInstance();</code></pre>
    </li>
    <li>
      <strong>Set meta variables for the current page:</strong>
      <pre style="background: #f4f4f4; padding: 1rem; border-radius: 6px; overflow-x: auto;"><code>$pageMeta->setMetaVariable('page-title', 'My Awesome Page');
$pageMeta->setMetaVariable('description', 'A short description of this page.');</code></pre>
    </li>
    <li>
      <strong>Retrieve meta variables anywhere in your view:</strong>
      <pre style="background: #f4f4f4; padding: 1rem; border-radius: 6px; overflow-x: auto;"><code>$currentTitle = $pageMeta->getMetaVariable('page-title');</code></pre>
    </li>
  </ol>

  <h2 style="color: #333;">🖥 App.pvue Example</h2>
  <p>Use MetaControl in your main app wrapper to manage page titles dynamically:</p>
  <pre style="background: #272822; color: #f8f8f2; padding: 1rem; border-radius: 6px; overflow-x: auto;"><code>&lt;script&gt;
$pageMeta = \PHPueExt\MetaControl::getInstance();

// If user navigates away or opens instance
if ($pageMeta->getRendered()) {
    $pageMeta->unsetMetaVariables();
    $pageMeta->setRendered(false);
}

$pageTitle = $pageMeta->getMetaVariable('page-title');

if (!empty($pageTitle))
    $pageTitle = "&lt;title&gt;$pageTitle&lt;/title&gt;";

$pageMeta->setRendered(true);
&lt;/script&gt;

&lt;!-- App.pvue only use static headers unless you use {{ $var }} 
(PHP IS NOT ALLOWED - AT LEAST THIS WAS THE CASE WHEN THIS WAS DESIGNED 
(HENCE THE EXTENSION)) --&gt;
&lt;header&gt;
    {{$pageTitle}}
&lt;/header&gt;
</code></pre>

  <h2 style="color: #333;">📄 views/index.pvue Example</h2>
  <p>Set or update page metadata within a specific view:</p>
  <pre style="background: #272822; color: #f8f8f2; padding: 1rem; border-radius: 6px; overflow-x: auto;"><code>&lt;script&gt;
$pageMeta = \PHPueExt\MetaControl::getInstance();

// Change if ?page-title="Page Name" given
if (isset($_GET['page-title'])) {
    $pageMeta->setMetaVariable('page-title', 'UE-abc');
    $_SESSION['bMetaSet'] = true;
}

$currentTitle = $pageMeta->getMetaVariable('page-title');
&lt;/script&gt;

&lt;!-- views/ only use static headers --&gt;
&lt;header&gt;
   &lt;!-- Initial Title  --&gt;
   &lt;title&gt;Page Name&lt;/title&gt; 
&lt;/header&gt;

&lt;cscript&gt;
const currentTitle = "&lt;?= addslashes($currentTitle ?? '') ?&gt;";
if (currentTitle) {
    document.title = currentTitle;
}
&lt;/cscript&gt;
</code></pre>

  <h2 style="color: #333;">✨ Key Features</h2>
  <ul>
    <li><strong>Singleton access:</strong> Only one instance per page ensures consistency.</li>
    <li><strong>Dynamic updates:</strong> Change metadata without hardcoding values in your HTML head.</li>
    <li><strong>Session awareness:</strong> Track if metadata has been set using <code>$_SESSION['bMetaSet']</code>.</li>
    <li><strong>Framework-agnostic:</strong> Works in any PHPue page without extra setup.</li>
  </ul>

  <h2 style="color: #333;">🚀 Summary</h2>
  <p>With <strong>MetaControl</strong>, your PHPue pages remain organized, dynamic, and SEO-ready without repetitive boilerplate. Set your metadata once and access it anywhere in your page templates or views.</p>
</div>