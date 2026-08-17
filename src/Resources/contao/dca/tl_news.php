<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;

$strTable = 'tl_news';

if(!is_array($GLOBALS['TL_DCA'][$strTable]) || count($GLOBALS['TL_DCA'][$strTable]) <= 0){
    return;
}

PaletteManipulator::create()
    ->addField('generate_seo','meta_legend',PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', $strTable);


$GLOBALS['TL_DCA'][$strTable]['fields']['generate_seo'] = [
    'input_field_callback'    => ['tl_news_gptbundle', 'generateButton'],
];



class tl_news_gptbundle extends Contao\Backend {

    /** Return a button to generate AI SEO Content
     * @param \Contao\DataContainer $dc
     * @return string
     */
    public function generateButton(\Contao\DataContainer $dc) {
        $strContent = '
            <script>
                const queryString = window.location.search;
                const urlParams = new URLSearchParams(queryString);
                const table = urlParams.get("table");
                function generateSeo(btn,id,mode) {
                                
                    const fetchPromise = fetch("/_gpt?id="+id+"&mode="+mode+"&table="+table);
                    const titleField = document.getElementById("ctrl_pageTitle");
                    const descField = document.getElementById("ctrl_description");

                    btn.disabled = true;

                    console.log("🪄 Lets do some AI Magic 🪄");

                    fetchPromise.then(async response => {
                        const content = await response.json();
                        if (!response.ok || content.success !== true) {
                            throw new Error(content.content || "The SEO content could not be generated.");
                        }
                        return content;
                    }).then(content => {
                        if(mode == "title") {
                            titleField.value = content.content;
                            // trigger this damn SERP preview
                            titleField.dispatchEvent(new Event("input", { bubbles: true }));
                        } else if(mode == "description") {
                            descField.innerHTML = content.content;
                            // trigger this damn SERP preview
                            descField.dispatchEvent(new Event("input", { bubbles: true }));
                        }
                        console.log("MAGIC 🪄🎩");
                    }).catch(error => {
                        alert(error.message || "The SEO content could not be generated.");
                    }).finally(() => {
                        btn.disabled = false;
                    });
                }
            </script>
            <div class="widget" style="margin-top:10px;">
                <button class="tl_submit" style="margin-right:5px;" id="ft_screenshot" onclick="generateSeo(this,'.$dc->id.',\'title\');return false">SEO Titel generieren</button>
                <button class="tl_submit" id="ft_screenshot" onclick="generateSeo(this,'.$dc->id.',\'description\');return false">SEO Beschreibung generieren</button>
            </div>  
        ';
        return $strContent;
    }
}
