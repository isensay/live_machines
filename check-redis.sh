echo "=== Redis Status ===" && docker exec Redis redis-cli -a 11 INFO memory | grep -E "used_memory_human|used_memory_rss_human|mem_fragmentation_ratio|maxmemory_human" | while read line; do
  key=$(echo $line | cut -d: -f1)
  value=$(echo $line | cut -d: -f2-)
  case $key in
    "used_memory_human") echo "📦 Данные: $value" ;;
    "used_memory_rss_human") echo "💾 Занято: $value" ;;
    "mem_fragmentation_ratio") echo "🔍 Фрагментация: $value" ;;
    "maxmemory_human") echo "🎯 Лимит: $value" ;;
  esac
done
