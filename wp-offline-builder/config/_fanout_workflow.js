export const meta = {
  name: 'growhaven-articles-fanout',
  description: 'Draft the remaining 86 GrowHaven articles from per-slug briefs',
  phases: [{ title: 'Draft', detail: 'one writer subagent per article', model: 'sonnet' }],
}

phase('Draft')
const ENG = 'D:/1Agent1/wp-offline-builder'
const slugs = ["start-vegetable-seeds-indoors", "build-a-raised-garden-bed", "easiest-vegetables-for-beginners", "raised-bed-vs-in-ground-vegetables", "why-tomato-leaves-curl", "grow-lettuce-year-round", "companion-planting-vegetables", "grow-potatoes-in-bags", "kitchen-garden-layout-ideas", "common-vegetable-garden-mistakes", "grow-peppers-from-seed", "first-vegetable-garden-case-study", "succession-planting-explained", "grow-herbs-on-windowsill", "design-a-flower-border", "plant-spring-flowering-bulbs", "annuals-vs-perennials", "best-flowers-for-pollinators", "why-roses-arent-blooming", "grow-sunflowers-tall", "deadheading-flowers-explained", "cottage-garden-flower-ideas", "grow-dahlias-from-tubers", "flower-bed-design-mistakes", "shade-loving-flowering-plants", "cut-flower-garden-case-study", "grow-flowers-in-containers", "understanding-bloom-times", "best-low-light-houseplants", "propagate-pothos-in-water", "why-houseplant-leaves-turn-yellow", "care-for-a-monstera", "best-houseplants-for-beginners", "how-often-to-water-houseplants", "terracotta-vs-plastic-pots", "repot-a-rootbound-houseplant", "style-houseplants-in-living-room", "fix-overwatered-houseplant", "humidity-loving-indoor-plants", "understanding-indoor-plant-light", "grow-herbs-indoors-winter", "propagate-succulents-from-leaves", "make-compost-fast", "test-your-garden-soil", "signs-of-unhealthy-soil", "mulching-benefits-explained", "improve-clay-soil", "common-watering-mistakes", "best-organic-soil-amendments", "start-a-compost-bin", "identify-common-garden-pests", "natural-pest-control-methods", "understanding-soil-ph", "diagnose-plant-diseases", "feed-plants-naturally", "revive-dead-garden-soil-case-study", "plant-a-fruit-tree", "prune-apple-trees-winter", "best-fruit-trees-for-small-gardens", "why-fruit-tree-not-fruiting", "grow-fruit-trees-in-pots", "bare-root-vs-potted-trees", "best-shrubs-for-privacy", "plant-berry-bushes", "understanding-tree-pollination", "fruit-tree-pruning-mistakes", "prune-flowering-shrubs", "backyard-orchard-case-study", "protect-fruit-trees-from-pests", "best-low-maintenance-shrubs", "understanding-tree-rootstocks", "spring-garden-checklist", "prepare-garden-for-winter", "what-to-plant-in-autumn", "build-a-cold-frame", "summer-garden-watering-guide", "essential-garden-tools", "plan-a-garden-from-scratch", "raised-bed-vs-greenhouse", "winter-gardening-mistakes", "build-a-vertical-garden", "understanding-frost-dates", "weekend-garden-projects", "month-by-month-garden-jobs", "first-year-garden-case-study", "set-up-drip-irrigation"]

function writerPrompt(slug) {
  return `You are a staff writer for GrowHaven, a home-gardening authority blog. Write ONE publish-ready article.

Step 1 - Read the full style guide: ${ENG}/references/content-style-guide.md
Step 2 - Read your assignment brief (JSON): ${ENG}/content/_plan/${slug}.json
   It has: title, archetype, category_title, author_name, author_voice, angle, internal_links (a list of slugs).
Step 3 - Write the article as RAW HTML to this exact path: ${ENG}/content/${slug}.html

Hard requirements:
- First line MUST be: <!--excerpt: 1-2 sentence summary, max 30 words-->
- Then the body only: NO <html>/<head>/<body>, NO <h1> (the theme renders the title), NO inline styles.
- 1,000-1,400 words. Short paragraphs (2-3 sentences), lots of whitespace.
- Intro: 2-3 paragraphs that open on a specific scene or number. Do NOT start with "In this article" / "In this guide".
- 5-7 <h2> sections shaped to the archetype (the guide explains each shape). Use <h3> where useful. Vary H2 phrasings.
- Include at least one <div class="callout"><strong>Label</strong><p>...</p></div> or <div class="tip-box">...</div>.
- If the archetype is "comparison", include a <div class="table-scroll"><table><thead>...</thead><tbody>...</tbody></table></div>.
- Write in the brief author_voice. Use real examples, common mistakes, small first-person asides. Vary sentence length so it reads human.
- Weave EACH internal_links slug into the body as a contextual link: <a href="/THE-SLUG/">descriptive anchor text</a>. Use each at least once, naturally (not a dumped list).
- End with <div class="faq"> containing EXACTLY 3 items, each: <div class="faq__item"><p class="faq__q">Question?</p><div class="faq__a"><p>Answer.</p></div></div>. Then a short 2-4 sentence closing paragraph (NO "Conclusion" heading).
- Gardening safety: follow-the-label for any chemicals, note plant toxicity to people/pets where relevant, no guarantees, no fear-mongering.
- NEVER use these phrases: the bottom line, in conclusion, ultimately, when it comes to, in todays world, needless to say, as mentioned above, unlock, dive in, game-changer, navigate the world of, supercharge, in this article, in this guide.
- Use ONLY these tags: <p>,<h2>,<h3>,<ul>,<ol>,<li>,<strong>,<em>,<a>,<blockquote>, plus the component divs above.

After writing the file, return exactly: wrote ${slug} (<word_count> words)`
}

const results = await parallel(
  slugs.map((s) => () => agent(writerPrompt(s), { label: 'write:' + s, phase: 'Draft', model: 'sonnet' }))
)
return { requested: slugs.length, completed: results.filter(Boolean).length, results }
