import { createHash } from 'node:crypto';
import { readFile, readdir, access } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath(new URL('../', import.meta.url));
const errors = [];
const sources = JSON.parse((await readFile(path.join(root, '.agents/skills-sources.json'), 'utf8')).replace(/^\uFEFF/, ''));
const renderedSkillFiles = [];
for (const source of sources) {
    const skillPath = `.agents/skills/${source.name}`;
    const entryPoint = `${skillPath}/SKILL.md`;
    const content = await readFile(path.join(root, entryPoint));
    if (createHash('sha256').update(content).digest('hex').toUpperCase() !== source.skillSha256) errors.push(`Skill hash mismatch: ${source.name}`);
    if (source.skillSource) renderedSkillFiles.push(entryPoint);
    for (const upstream of source.upstreamFiles || []) {
        const bytes = await readFile(path.join(root, skillPath, upstream.file));
        if (createHash('sha256').update(bytes).digest('hex').toUpperCase() !== upstream.sha256) errors.push(`Upstream skill file hash mismatch: ${source.name}/${upstream.file}`);
    }
    for (const generated of source.localGeneratedFiles || []) {
        const filename = `${skillPath}/${generated.file}`;
        const bytes = await readFile(path.join(root, filename));
        if (createHash('sha256').update(bytes).digest('hex').toUpperCase() !== generated.sha256) errors.push(`Generated rule hash mismatch: ${generated.file}`);
        if (source.skillSource) renderedSkillFiles.push(filename);
    }
}
const dirs = await readdir(path.join(root, '.agents/skills'));
const localSkills = ['review-pr', 'update-api-contract', 'validate-web', 'verify-release'];
for (const name of dirs) {
    if (!sources.some(source => source.name === name) && !localSkills.includes(name)) errors.push(`Unregistered skill: ${name}`);
}
const ruleFiles = (await readdir(path.join(root, '.agents/rules'))).filter(name => name.endsWith('.md')).map(name => `.agents/rules/${name}`);
const localSkillFiles = localSkills.map(name => `.agents/skills/${name}/SKILL.md`);
for (const filename of [...ruleFiles, ...localSkillFiles, ...renderedSkillFiles, 'AGENTS.md', '.agents/README.md', '.agents/skill-coverage.md', '.agents/evaluation/README.md', 'README.md', 'DEPLOY.md', 'docs/testing.md', 'docs/design.md', 'docs/source-navigation.md', 'docs/operations-logging.md', 'docs/release-recovery.md', 'deploy/full-site/INSTALL.md']) {
    const content = await readFile(path.join(root, filename), 'utf8');
    for (const match of content.matchAll(/\]\(([^\s)]+)\)/g)) {
        if (/^(?:https?:|#)/.test(match[1])) continue;
        const target = path.resolve(root, path.dirname(filename), match[1].split('#')[0]);
        if (!target.startsWith(root)) { errors.push(`Guide link escapes project: ${filename}`); continue; }
        try { await access(target); } catch { errors.push(`Missing link in ${filename}: ${match[1]}`); }
    }
    if (/build-panel\.mjs|admin-public\//.test(content)) errors.push(`Obsolete panel build reference: ${filename}`);
}
if (errors.length) throw new Error(errors.join('\n'));
console.log(`Guidance links and ${sources.length} upstream skill entry points verified.`);
